# Laporan Audit & Remediasi Keamanan Sistem: E-Arsip V2

**Tanggal Audit & Remediasi:** 29 Oktober 2026  
**Target Sistem:** E-Arsip V2 (Laravel 11, Livewire 3, MySQL, Dual-layer Storage NAS)  
**Metodologi:** White-box Source Code Security Audit (@security-audit) & Minimalist Defensive Remediation (@ponytail)  
**Status Uji Akhir:** 174 Tests Passed (772 assertions), 0 Failures  

---

## 1. Ringkasan Eksekutif

Pemeriksaan keamanan dilakukan untuk mengidentifikasi kerentanan yang melanggar *trust boundary* pada sistem pengelolaan arsip formal (Records Management System). Sebanyak 8 temuan keamanan berhasil diidentifikasi, diklasifikasikan tingkat risikonya, dan telah diperbaiki secara tuntas (*fully remediated*) tanpa merusak alur kerja aplikasi eksisting.

### Matriks Status Temuan Keamanan

| ID | Kategori / Judul Kerentanan | Severity | Status Awal | Status Akhir |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | Privilege Escalation via Unprotected Role Edit Route & Component | **Critical** | Confirmed | **Resolved** |
| **SEC-02** | Authentication Bypass pada Akun Karyawan Berstatus Nonaktif (`inactive`) | **High** | Confirmed | **Resolved** |
| **SEC-03** | Insecure State Transition & Authorization Bypass pada Pemusnahan Arsip | **High** | Confirmed | **Resolved** |
| **SEC-04** | Kebocoran Metadata Dokumen Rahasia pada Registri Surat Masuk/Keluar/Khusus | **High** | Confirmed | **Resolved** |
| **SEC-05** | Livewire Direct Action Invocation pada Modul Pengaturan Sensitif | **High** | Confirmed | **Resolved** |
| **SEC-06** | Missing Route Middleware & Missing Action Guard pada Resubmit Surat | **Medium** | Confirmed | **Resolved** |
| **SEC-07** | Otorisasi Preview Berkas Bypass saat Relasi Dokumen Orphan/Terhapus | **Medium** | Confirmed | **Resolved** |
| **SEC-08** | Ketiadaan Rate Limiting / Proteksi Brute-Force pada Endpoint Autentikasi | **Medium** | Confirmed | **Resolved** |

---

## 2. Rincian Temuan & Langkah Remediasi

### SEC-01: Privilege Escalation via Unprotected Role Edit Route & Component
* **Severity**: **Critical**
* **Akar Masalah**:
  1. Pada `routes/web.php`, rute `Route::get('/roles/{role}/edit', ...)` ditempatkan di luar grup proteksi `Route::middleware('module:roles')`.
  2. Komponen `App\Livewire\Settings\RoleEditor` tidak memvalidasi hak akses pengguna pada method `mount()` maupun saat method mutasi `save()` dieksekusi.
* **Dampak**: Pengguna terautentikasi level staf biasa mana pun dapat membuka form edit role atau mengirim request untuk mengubah hak akses role miliknya sendiri menjadi Superadmin.
* **Remediasi yang Diterapkan**:
  * Memindahkan rute `roles.edit` ke dalam grup `middleware('module:roles')` di `routes/web.php`.
  * Menambahkan lifecycle guard `boot()` pada `RoleEditor.php`:
    ```php
    public function boot()
    {
        $user = Auth::user();
        if (!$user?->isSuperAdmin() && !$user?->hasModuleAccess('roles') && $user?->role?->name !== 'Admin') {
            abort(403, 'Akses ditolak.');
        }
    }
    ```

---

### SEC-02: Authentication Bypass pada Akun Karyawan Nonaktif (`inactive`)
* **Severity**: **High**
* **Akar Masalah**:
  Pada `app/Livewire/Auth/Login.php`, pemanggilan `Auth::attempt($credentials)` hanya memverifikasi kecocokan `email`/`username` dan `password`. Kolom `status` tidak disertakan dalam pencocokan kredensial.
* **Dampak**: Akun pengguna/karyawan yang telah dinonaktifkan atau diberhentikan oleh administrator (`status = 'inactive'`) tetap dapat masuk ke sistem.
* **Remediasi yang Diterapkan**:
  * Menambahkan parameter `'status' => 'active'` ke dalam array kredensial `Auth::attempt()`.
  * Memberikan penanganan error spesifik jika akun berstatus `inactive` mencoba login:
    ```php
    $credentials = $isEmail
        ? ['email' => $this->email, 'password' => $this->password, 'status' => 'active']
        : ['username' => $this->email, 'password' => $this->password, 'status' => 'active'];

    if (Auth::attempt($credentials, $this->remember)) {
        // Berhasil login
    } else {
        // Cek apakah akun dinonaktifkan
        $user = User::where($field, $this->email)->first();
        if ($user && $user->status === 'inactive' && Hash::check($this->password, $user->password)) {
            $this->addError('email', 'Akun Anda dinonaktifkan oleh administrator. Silakan hubungi pengelola sistem.');
            return;
        }
    }
    ```

---

### SEC-03: Insecure State Transition & Authorization Bypass pada Pemusnahan Arsip
* **Severity**: **High**
* **Akar Masalah**:
  1. Method `submitProposal()` pada `DestructionIndex.php` tidak membatasi role pengusul dan tidak memverifikasi apakah arsip yang diajukan benar-benar telah melampaui masa retensi inaktif (`retention_end_date <= now()`).
  2. Method `executeDestruction()` tidak memvalidasi status record pemusnahan sebelum mengubah status dokumen menjadi `disposed` dan mengunggah Berita Acara (BA).
* **Dampak**: Dokumen aktif yang belum habis masa retensinya dapat diusulkan oleh staf biasa, serta dapat dieksekusi secara instan tanpa menunggu persetujuan (*approval*) berjenjang.
* **Remediasi yang Diterapkan**:
  * Pada `submitProposal()`, menambahkan pengecekan otorisasi wewenang (`isTapersip()`, `isSuperAdmin()`, atau modul `destruction`) serta validasi database bahwa seluruh ID dokumen yang dipilih harus memenuhi syarat retensi habis dan belum pernah diusulkan.
  * Pada `executeDestruction()`, menambahkan guard validasi:
    ```php
    if ($records->contains(fn($r) => $r->status !== 'approved')) {
        $this->dispatch('notify', [
            'message' => 'Hanya usulan pemusnahan yang telah disetujui resmi yang dapat dieksekusi.',
            'type' => 'error'
        ]);
        return;
    }
    ```

---

### SEC-04: Kebocoran Metadata Dokumen Rahasia pada Registri Surat
* **Severity**: **High**
* **Akar Masalah**:
  Pada `Incoming\Index.php`, `Outgoing\Index.php`, dan `Internal\Index.php`, kueri dokumen pada method `render()` mengambil seluruh baris data tanpa menyaring naskah berklasifikasi `penting` atau `rahasia`. Meskipun tombol preview fisik disembunyikan di Blade template, metadata dokumen (Nomor Surat, Perihal, Pengirim, Penerima) tetap dikirimkan ke browser via payload JSON Livewire.
* **Dampak**: Pengguna staf umum dapat mengintip perihal dan data sensitif surat rahasia direksi/perusahaan.
* **Remediasi yang Diterapkan**:
  Menambahkan *query-level boundary filtering* pada method `render()` di ketiga komponen registri surat:
  ```php
  $user = Auth::user();
  if ($user && !$user->isSuperAdmin() && !$user->isTapersip()) {
      $canViewRahasia = $user->hasDocumentPermission('view', 'rahasia');
      $canViewPenting = $user->hasDocumentPermission('view', 'penting');
      $userId = $user->id;
      $userUnitId = $user->unit_id;

      $query->where(function ($q) use ($canViewRahasia, $canViewPenting, $userId, $userUnitId) {
          $q->where('sifat', 'biasa')
            ->orWhere('created_by', $userId);

          if ($canViewRahasia) {
              $q->orWhere('sifat', 'rahasia');
          }

          if ($canViewPenting) {
              $q->orWhere('sifat', 'penting');
          } elseif ($userUnitId) {
              $q->orWhere(function ($qp) use ($userUnitId) {
                  $qp->where('sifat', 'penting')
                     ->where(function ($qu) use ($userUnitId) {
                         $qu->where('unit_id', $userUnitId)
                            ->orWhereHas('recipients', function ($qr) use ($userUnitId) {
                                $qr->where('recipient_id', $userUnitId);
                            });
                     });
              });
          }
      });
  }
  ```

---

### SEC-05: Livewire Direct Action Invocation pada Modul Pengaturan Sensitif
* **Severity**: **High**
* **Akar Masalah**:
  Request mutasi state Livewire diproses via endpoint HTTP generic `POST /livewire/update` yang tidak menjalankan route middleware halaman awal. Komponen pengaturan seperti `RoleIndex`, `UserIndex`, `OrganizationIndex`, `ArchiveClassificationIndex`, dan `AuditTrailIndex` sebelumnya tidak memverifikasi hak akses pada level komponen.
* **Dampak**: Penyerang terautentikasi dapat menembakkan request Livewire langsung untuk memanggil method `delete()`, `save()`, atau membaca audit trail tanpa melewati pengecekan menu web.
* **Remediasi yang Diterapkan**:
  Menambahkan method lifecycle `boot()` di setiap komponen pengaturan:
  * `UserIndex` & `RoleIndex`: Hanya Superadmin, Admin, atau akun dengan akses modul `roles`.
  * `OrganizationIndex`: Hanya Superadmin atau modul `master_data`.
  * `ArchiveClassificationIndex`: Hanya Superadmin atau modul `classification`.
  * `AuditTrailIndex`: Hanya Superadmin atau modul `audit`.

---

### SEC-06: Missing Route Middleware & Missing Action Guard pada Resubmit Surat
* **Severity**: **Medium**
* **Akar Masalah**:
  1. Rute `/incoming/{document}/edit` di `routes/web.php` tidak memiliki middleware pembatas hak input.
  2. Method `resubmit()` pada komponen `Incoming\Edit.php` tidak memvalidasi ulang apakah dokumen tersebut milik pengguna yang sedang login dan berstatus `rejected`.
* **Dampak**: Pengguna lain berpotensi memanggil method `resubmit()` untuk mengubah data surat masuk milik staf lain.
* **Remediasi yang Diterapkan**:
  * Menambahkan middleware `module:input_incoming` pada rute `incoming.edit` di `routes/web.php`.
  * Menambahkan guard di method `resubmit()` pada `app/Livewire/Registry/Incoming/Edit.php`:
    ```php
    if ($this->document->created_by !== Auth::id() || $this->document->status !== 'rejected') {
        abort(403, 'Akses ditolak.');
    }
    ```

---

### SEC-07: Otorisasi Preview Berkas Bypass saat Relasi Dokumen Orphan/Terhapus
* **Severity**: **Medium**
* **Akar Masalah**:
  Pada rute `/document/preview/{version}` di `routes/web.php`, pengecekan hak akses dibungkus kondisi `if ($document && !Auth::user()->hasDocumentPermission(...))`. Jika dokumen induk di-soft delete atau bernilai null, kondisi tersebut bernilai false dan response file tetap dikirimkan ke client.
* **Dampak**: Akses tidak sah ke file fisik arsip yang dokumen induknya telah terhapus.
* **Remediasi yang Diterapkan**:
  Memvalidasi keberadaan dokumen induk sebelum memeriksa izin berkas:
  ```php
  $document = $version->document;
  if (!$document) {
      abort(404, 'Dokumen arsip tidak ditemukan.');
  }

  if (!Auth::user()->hasDocumentPermission('view', $document->sifat ?? 'biasa')) {
      abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat pratinjau dokumen dengan sifat ini.');
  }
  ```

---

### SEC-08: Ketiadaan Rate Limiting pada Endpoint Autentikasi
* **Severity**: **Medium**
* **Akar Masalah**:
  Method `authenticate()` pada `App\Livewire\Auth\Login` tidak membatasi frekuensi percobaan login yang gagal.
* **Dampak**: Rentan terhadap serangan *brute force* dan *credential stuffing* password karyawan secara masif.
* **Remediasi yang Diterapkan**:
  Mengintegrasikan `Illuminate\Support\Facades\RateLimiter` dengan pembatasan maksimal 5 kegagalan login per kombinasi email/username dan alamat IP client:
  ```php
  $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());
  if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
      $seconds = RateLimiter::availableIn($throttleKey);
      $this->addError('email', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.");
      return;
  }
  ```

---

## 3. Hasil Verifikasi Keamanan Pasca-Remediasi

### Pengujian Otomatis Khusus Keamanan (`SecurityRemediationTest`)
Dibuat test suite otomatis di `tests/Feature/SecurityRemediationTest.php` untuk memvalidasi seluruh perbaikan secara independen:

| Test Case | Aspek yang Diuji | Status |
| :--- | :--- | :--- |
| `test_inactive_user_cannot_login` | Menolak autentikasi akun berstatus `inactive` | **PASSED** |
| `test_login_rate_limiting_triggers_after_multiple_failures` | Rate limiter memicu lockout setelah 5x gagal | **PASSED** |
| `test_unauthorized_user_cannot_access_role_editor_or_user_index` | Livewire RoleEditor menolak staf biasa (403) | **PASSED** |
| `test_unauthorized_user_cannot_access_user_index` | Livewire UserIndex menolak staf biasa (403) | **PASSED** |
| `test_unapproved_destruction_cannot_be_executed` | Eksekusi pemusnahan draft berstatus proposed ditolak | **PASSED** |
| `test_confidential_document_is_hidden_from_unauthorized_staff_in_incoming_index` | Surat rahasia tidak muncul di tabel staf biasa | **PASSED** |
| `test_document_preview_aborts_404_if_document_is_orphaned` | Preview berkas orphan mengembalikan HTTP 404 | **PASSED** |

### Hasil Uji Regresi Keseluruhan Sistem
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\ArchiveDestructionTest
   PASS  Tests\Feature\CancelledDocumentWorkflowTest
   PASS  Tests\Feature\DashboardActivityLogTest
   PASS  Tests\Feature\DashboardRealTimeSyncTest
   PASS  Tests\Feature\DocumentArchivedNotificationTest
   PASS  Tests\Feature\DocumentCorrectionAndRevisionTest
   PASS  Tests\Feature\DocumentLoanNotificationTest
   PASS  Tests\Feature\DocumentLoanTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\GlobalNotificationToastTest
   PASS  Tests\Feature\IncomingMailTest
   PASS  Tests\Feature\MasterDataManagementTest
   PASS  Tests\Feature\NasStorageTest
   PASS  Tests\Feature\OrganizationUnitTest
   PASS  Tests\Feature\OverdueLoanReminderCommandTest
   PASS  Tests\Feature\RelocateDocumentTest
   PASS  Tests\Feature\SecurityRemediationTest
   PASS  Tests\Feature\SequenceNumberingTest
   PASS  Tests\Feature\SpecialLetterTest
   PASS  Tests\Feature\TapersipRealTimeNotificationTest
   PASS  Tests\Feature\UserAndRoleManagementTest

  Tests:    174 passed (772 assertions)
  Duration: 26.71 seconds
```

---

## 4. Hasil Audit Menyeluruh Vektor Serangan Lain

Setelah menyelesaikan perbaikan terhadap temuan utama, dilakukan audit komprehensif terhadap seluruh basis kode untuk memeriksa potensi vektor serangan lainnya:

1. **SQL Injection (SQLi)**:
   * **Hasil:** **Aman**.
   * Seluruh query memanfaatkan Eloquent ORM atau query builder dengan parameterized bindings PDO. Penggunaan ekspresi mentah tunggal pada `DB::raw('MAX(id) as latest_id')` tidak melibatkan input dari pengguna.
2. **Command / Code Injection (RCE)**:
   * **Hasil:** **Aman**.
   * Tidak ada fungsi eksekusi sistem operasi (`exec`, `system`, `passthru`, `shell_exec`, `proc_open`) yang digunakan dalam codebase. Pengecekan status NAS (`NasHealthService.php`) dilakukan langsung melalui API Storage filesystem lokal.
3. **Cross-Site Scripting (XSS)**:
   * **Hasil:** **Aman**.
   * Seluruh template Blade di `resources/views/` menggunakan sintaks *auto-escaping* `{{ ... }}`. Tidak ada penggunaan tag `{!! ... !!}` tanpa sanitasi.
4. **File Upload Security**:
   * **Hasil:** **Aman**.
   * Validasi MIME type (`mimes:pdf`) dan batasan ukuran berkas (`max:10240` / 10MB) diterapkan konsisten di seluruh form unggah berkas fisik. Nama berkas di NAS disanitasi dari karakter traversal (`/`, `\`) dan karakter ilegal sistem operasi (`: * ? " < > |`).
5. **Mass Assignment**:
   * **Hasil:** **Aman**.
   * Seluruh model Eloquent mendefinisikan array `$fillable` secara eksplisit dan tidak ada model yang menggunakan `$guarded = []`.

---

## 5. Rekomendasi Hardening Lingkungan Produksi

1. **Protokol HTTPS & Secure Cookie**:
   Pastikan pada berkas `.env` server produksi disetel `SESSION_SECURE_COOKIE=true` agar cookie sesi hanya dikirimkan melalui enkripsi TLS/HTTPS.
2. **Isolasi Storage Statis Web Server (Telah Diterapkan)**:
   Aset statis UI (seperti logo `logo_cms_hd_png.svg`) telah dipindahkan langsung ke direktori publik murni (`public/assets/`), dan konfigurasi fallback disk NAS telah dialihkan ke direktori private `storage/app/nas`. Dengan arsitektur ini, logo selalu tampil tanpa memerlukan `php artisan storage:link`, dan dokumen arsip perusahaan tidak akan pernah bocor meskipun symlink publik dibuat.
3. **Driver Cache untuk Rate Limiting**:
   Gunakan driver cache seperti `redis` atau database pada environment produksi agar kunci *rate limiting* login tidak hilang saat worker PHP-FPM di-restart.
