<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

---

## Dokumentasi Implementasi E-Arsip Enterprise (Frontend & Auth)

### 1. Autentikasi (Login & Sesi)
- **Ekstensi PHP**: Mengaktifkan ekstensi `pdo_mysql` di `php.ini` pada environment lokal (MAMP) agar Laravel dapat terhubung secara native ke MySQL.
- **Modifikasi Database**: Menambahkan kolom `username` (unique) dan `role` (dengan default 'user') pada file migrasi tabel `users` (`0001_01_01_000000_create_users_table.php`). Memasukkan properti ini ke `$fillable` pada Model `User`.
- **Seeding Akun Superadmin**: Mengonfigurasi `DatabaseSeeder` untuk membuat satu akun master IT secara otomatis:
  - Username: `superadmin`
  - Password: `password`
  - Role: `superadmin`
- **Integrasi Livewire**: Menggunakan `Auth::attempt()` di dalam `App\Livewire\Auth\Login.php` untuk memvalidasi input kredensial. Login sukses akan meredirect user ke route `/dashboard`.
- **UI Login (Responsive)**: Membangun form login menggunakan layout `x-layouts.guest` (tanpa elemen sidebar Dasbor). Form ini dipastikan responsif dengan `w-full max-w-[420px]`, memiliki mekanisme *toggle Show/Hide Password* interaktif, serta menampilkan pesan *error validation* dari sisi Livewire dengan rapi.
- **Routing & Middleware**: Memproteksi rute `/dashboard` dan seluruh rute pengaturan (`/settings/*`) dengan middleware `auth`. Halaman `/login` dilindungi dengan middleware `guest`.

### 2. Modul "User & Role" (Manajemen Pengguna)
- **Halaman Daftar Pengguna (`/settings/users`)**: 
  - Mengonversi UI ke format komponen standar Livewire berbasis kelas (`App\Livewire\Settings\UserIndex.php`).
  - Menampilkan Data Table *mockup* interaktif yang memuat informasi profil akun (Nama, NIP, Unit Kerja, Jabatan, Role Sistem).
  - Menyediakan navigasi khusus berupa tombol "Manajemen Roles" di *header* untuk loncat langsung ke halaman konfigurasi matriks otorisasi.
- **UI Pop-up (Modal) Tambah Pengguna**: 
  - Diimplementasikan menggunakan komponen independen `<x-ui.modal>` yang stabil secara hierarki DOM.
  - Berisi form entri ganda untuk mendaftarkan Profil Diri dan menugaskan karyawan ke dalam Posisi/Otorisasi Sistem.

### 3. Modul Master Data Organisasi (Unit Kerja & Jabatan)
- **Halaman Organisasi (`/settings/organization`)**: 
  - Dikelola oleh Livewire class `App\Livewire\Settings\OrganizationIndex.php`.
  - Berkonsep *Tabbed Navigation* yang menaungi 2 tab utama: Tab Unit Kerja (Departemen) dan Tab Jabatan (Posisi).
- **Tab Unit Kerja**: Menampilkan tabel relasional antar departemen (Induk Unit) dan menyediakan form Modal untuk input Kode Unit beserta status operasinya.
- **Tab Jabatan**: Menampilkan tabel struktur level hierarki organisasi (dari Direksi Level 1 hingga Pelaksana Level 4). Form Modal memfasilitasi pendefinisian tugas dan otorisasi *approval* bawaan.
- **Perbaikan Arsitektur Utama (Bugfix)**: 
  - Membuang nilai `--spacing` yang destruktif pada `@theme` di file `app.css` (yang sebelumnya menyebabkan efek kompresi UI/Popsicle Stick).
  - Mengonversi *Slide-over* menjadi *Pop-up Modal* murni untuk mencegah pemotongan komponen oleh `overflow-x-hidden`.
  - Menghindari tag `x-teleport` agar validasi reaktif dari Livewire tidak kehilangan jejak DOM (*DOM diffing*).
  - Memperbaiki bug tag `</div>` ganda yang sebelumnya memicu `MultipleRootElementsDetectedException`.

### 4. Modul Klasifikasi Arsip & Jenis Dokumen
- **Halaman Klasifikasi (`/settings/classifications`)**: 
  - Diikat ke dalam Livewire class `App\Livewire\Settings\ArchiveClassificationIndex.php`.
  - **Tab Klasifikasi Arsip**: UI khusus untuk mengatur akar dan ranting subjek kearsipan (misal HM.01, HM.02) beserta formulasi penetapan masa aktif Jadwal Retensi Arsip (JRA) dan vonis nasib akhir berkas.
  - **Tab Jenis Dokumen**: Memetakan profil identitas tipe naskah (Surat Edaran, Memo, dll), sifat kerahasiaan *default*, dan pratinjau formasi penomoran otomatis secara *real-time*.

### 5. Backend Master Data & Autentikasi (Tahap 4)
- **Arsitektur Database**: Mengganti struktur string statis pada *user* menjadi relasi *Foreign Key* yang kompleks. Dibuat 7 tabel Master Data: `roles`, `permissions`, `role_permission` (Matriks 3 Dimensi: Role x Permission x Sifat Dokumen), `units`, `positions`, `classifications`, dan `document_types`. Seluruh tabel master mengimplementasikan fitur *SoftDeletes*.
- **Data Seeding Terpadu**: Pembuatan seeder lengkap (Unit, Jabatan, Role, Permission) dan memastikan superadmin memiliki seluruh relasi FK yang valid secara otomatis saat `php artisan migrate:fresh --seed` dijalankan.
- **Konektivitas Livewire CRUD**: Semua UI Master Data yang sebelumnya statis kini telah dihubungkan (*wired*) sepenuhnya dengan Eloquent Models (Read, Create, Update, Delete) menggunakan validasi dinamis dari Livewire.

### 6. Modul Registrasi Dokumen / Registry (Tahap 5)
- **Menu Navigasi Baru (UX)**: Memecah sidebar menjadi grup fungsional: **"Input Surat"** (Registrasi Surat Masuk, Surat Keluar, Memo Internal) dan **"Kearsipan"** (Explorer).
- **Form Split-Pane & Live Preview**: Membangun form registrasi responsif yang menggunakan *Split-Pane layout*: Kiri untuk formulir dinamis (termasuk *Radio Cards* pemilihan sifat surat), Kanan untuk pratinjau PDF langsung menggunakan fungsi *Drag & Drop*.
- **Inbox Explorer Loker (Sentralisasi)**: Mengadopsi arsitektur *Single Filing System*. Tabel `folders` dibuat secara mandiri untuk mewakili struktur map/loker penyimpanan. Loker ini bersifat global dan dirender dalam bentuk *Folder Tree* (Pohon Hierarki interaktif) di setiap halaman daftar arsip.

### 7. Workflow Engine & Persetujuan (Tahap 6)
- **Arsitektur Workflow ISO**: Membuat 3 tabel khusus pelacakan aliran persetujuan (`workflow_instances`, `workflow_steps`, `approvals`) yang berelasi dinamis ke tabel dokumen.
- **Panel Verifikasi & Approval (`/workflow/approvals`)**: Halaman *Task-Oriented* eksklusif yang memfilter antrean persetujuan spesifik untuk user yang sedang *login*. Menerapkan tata letak *Split-Pane*.
- **Logika Auto-Numbering**: Saat Surat Keluar atau Memo Internal disetujui secara final, sistem akan menghasilkan Nomor Surat Resmi secara otomatis dengan mengambil singkatan Klasifikasi, Kode Unit, dan Tahun berjalan (contoh: `001/HM.00/IT/2026`).
- **Modul Disposisi (`/workflow/dispositions`)**: Fitur delegasi tugas berbasis dokumen dengan dua tab fungsional (Beri Disposisi & Tindak Lanjut).

### 8. Metrik Dashboard & Kepatuhan Audit (Tahap Final / Penutup)
- **Live Dashboard**: Menghubungkan seluruh angka di *Bento Cards* dan *Tugas Saya* dengan kueri riil dari *database* (misal: Menghitung jumlah Verifikasi yang *pending*, Disposisi aktif pengguna, dan notifikasi JRA).
- **Audit Trail Log (`/settings/audit-trail`)**: Menyediakan tabel log *immutable* (tidak dapat diubah/dihapus) yang merender setiap rekam jejak sistem berdasarkan riwayat di database.
- **Usul Pemusnahan (JRA) (`/archive/destructions`)**: Menerapkan modul retensi arsip. Dokumen yang kolom `retention_end_date`-nya telah kadaluarsa akan otomatis masuk ke daftar antrean "Retensi Habis". Di sini, admin dapat mengusulkan pemusnahan (Destruction Proposal) dan mengunggah Berkas Berita Acara (BA) jika disetujui.

### 9. Perbaikan Kritis & Optimalisasi Lanjutan (Bug Fixes & Refinements)
- **Streaming PDF & Instant Preview**: Mengganti pemanggilan URL sementara dari server dengan **Client-Side Blob URL** menggunakan Alpine.js (URL.createObjectURL()). Hal ini menghasilkan pratinjau instan (tanpa *loading*) dan mencegah *browser* memaksa *download* file. Menggunakan tag <iframe> dengan injeksi parameter #toolbar=0&navpanes=0 untuk menciptakan tampilan pembaca PDF yang bersih tanpa gangguan menu (Zoom/Print).
- **Role-Based Access Control (RBAC) Engine**: Menyempurnakan pembacaan array JSON pada User.php (hasModuleAccess) dan mengunci seluruh komponen *Sidebar* dengan otorisasi kondisional. Menciptakan middleware CheckModuleAccess yang melindungi seluruh URL aplikasi; jika *user* nekat membobol URL tanpa hak akses, sistem akan melakukan *Force Logout* seketika beserta pesan peringatan keamanan.
- **Eskalasi Hierarki Disposisi Otomatis**: Membangun *Authority Resolver* pintar (getLedUnitIds()). Jika sebuah surat ditujukan ke unit yang tidak memiliki pimpinan (misal: Unit IT hanya diisi Staf), sistem akan secara otomatis melacak hierarki ke atas (berdasarkan parent_id) dan melempar tugas disposisi tersebut ke meja atasan terdekat (misal: Manajer Operasional).
- **Alur Verifikasi Terpusat (Task Pool)**: Merombak logika pengajuan dokumen agar tugas Verifikasi tidak lagi terkunci secara *hardcode* ke satu orang. Tugas kini masuk ke dalam "Kolam Antrean" yang bisa diambil oleh siapa pun yang memiliki hak akses menu Verifikator.
- **Penguncian Permanen (Smart Confirmation)**: Menambahkan lapisan keamanan pada form Tindak Lanjut Disposisi. Saat tugas ditandai "Selesai", *browser* memunculkan konfirmasi pencegah salah klik. Di *backend*, sistem langsung mengubah status dokumen menjadi Archived (Buku Tutup), menyembunyikan form aksi selamanya, dan merender kotak riwayat hijau (*Read-Only*).

### 10. Simplifikasi MVP — Penghapusan Permanen Workflow Engine
- **Keputusan Arsitektur Kritis**: Merombak total arah aplikasi dari *Full Digital Workflow* menjadi **Digital Repository** murni. Seluruh mekanisme persetujuan online (Verifikasi → Approval → Disposisi) dihapus secara permanen. Semua persetujuan dilakukan secara manual/offline di luar sistem; E-Arsip berfungsi khusus untuk pengarsipan dokumen final dan pemesanan nomor surat.
- **Penghapusan 5 Tabel Workflow dari Database & Filesystem**:
  - `workflow_instances` — Pelacakan instansi alur kerja.
  - `workflow_steps` — Langkah-langkah persetujuan bertahap.
  - `approvals` — Rekam jejak persetujuan verifikator/approver.
  - `dispositions` — Delegasi tugas disposisi pimpinan.
  - `follow_ups` — Tindak lanjut disposisi bawahan.
- **Penghapusan File Terkait**: Seluruh file migrasi, Model Eloquent (`WorkflowInstance`, `WorkflowStep`, `Approval`, `Disposition`, `FollowUp`), Livewire Component (Panel Verifikasi, Panel Approval, Panel Disposisi), dan Blade View terkait workflow telah dihapus secara permanen dari *filesystem*.
- **Pembersihan Route & Sidebar**: Seluruh rute `/workflow/*` dihapus dari `routes/web.php`. Grup sidebar "Tata Kelola & Workflow" (berisi Verifikasi & Approval, Disposisi) dihapus dari layout utama.
- **Migrasi Database Bersih**: Menjalankan `php artisan migrate:fresh --seed` untuk memastikan *database* bersih tanpa sisa tabel workflow.
- **Penambahan Status Baru**: Menambahkan nilai `waiting_for_file` ke enum status dokumen pada migrasi `documents`, untuk mendukung alur *Booking Nomor Surat* di mana dokumen dibuat tanpa file PDF.

### 11. Sistem Booking Nomor Surat & Auto-Numbering
- **Konsep Alur Booking**: Staf mengisi formulir Surat Keluar/Memo Internal **tanpa wajib melampirkan PDF** → Sistem men-*generate* Nomor Surat Resmi secara otomatis → Pop-up modal menampilkan nomor yang telah terbit → Staf menyalin nomor ke dokumen Word → Mencetak & menandatangani fisik → Menyetorkan dokumen fisik ke Tapersip → Tapersip mengunggah hasil scan PDF ke sistem.
- **Mesin Auto-Numbering**:
  - Mengambil pola dari kolom `document_types.numbering_format` (contoh: `{NO}/M-{UNIT}-{kodeklasi}/{MM}/{YYYY}`).
  - **Variabel yang Didukung**:
    - `{NO}` — Nomor urut otomatis per `document_type_id` per tahun, di-*pad* 3 digit (001, 002, 003, dst.).
    - `{UNIT}` — Kode singkatan Unit Kerja pengirim (dari tabel `units`).
    - `{kodeklasi}` / `{CLASS}` — Kode Klasifikasi Arsip (dari tabel `classifications`).
    - `{YYYY}` — Tahun berjalan (4 digit).
    - `{MM}` — Bulan berjalan (2 digit).
  - **Logika Penghitungan Urut**: Sistem menghitung `Document::where('document_type_id', $id)->whereYear('document_date', $tahun)->count()` lalu menambahkan 1 untuk mendapatkan nomor urut berikutnya. Reset otomatis setiap pergantian tahun.
- **Pop-up Modal Sukses**: Setelah nomor terbit, ditampilkan modal berisi nomor yang telah di-*generate* dengan tombol "Selesai & Kembali ke Dashboard". **Tidak ada redirect otomatis** ke halaman arsip (karena staf mungkin tidak memiliki akses ke modul Kearsipan).
- **Dukungan Dual Mode pada Form**:
  - **Mode Arsip Langsung**: Jika staf mengunggah file PDF, dokumen langsung berstatus `archived`.
  - **Mode Booking**: Jika tanpa PDF, dokumen berstatus `waiting_for_file`, menunggu upload final dari Tapersip.
- **File Terkait**:
  - `app/Livewire/Registry/Outgoing/Create.php` — Form Surat Keluar dengan *auto-numbering*.
  - `app/Livewire/Registry/Internal/Create.php` — Form Memo Internal dengan *auto-numbering* + *auto-default* ke jenis "Memo Internal".

### 12. Alur Upload Final oleh Tapersip
- **Tab Ganda pada Halaman Arsip**: Halaman index Surat Keluar (`/registry/outgoing`) dan Memo Internal (`/registry/internal`) kini memiliki 2 tab:
  - **Tab "Menunggu Upload"**: Menampilkan dokumen berstatus `waiting_for_file` yang telah di-*booking* nomornya oleh staf tetapi belum memiliki file PDF.
  - **Tab "Arsip Permanen"**: Menampilkan dokumen berstatus `archived` yang sudah lengkap dengan file PDF.
- **Modal Upload Final**: Tapersip dapat mengklik tombol "Upload PDF" pada baris dokumen di tab "Menunggu Upload". Sistem menampilkan modal berisi informasi ringkas dokumen dan area *drag & drop* untuk mengunggah file PDF hasil scan.
- **Method `saveFinalFile()`**: Saat file PDF diunggah, sistem membuat entri baru di tabel `document_versions` (versi pertama dari dokumen) dan mengubah status dokumen dari `waiting_for_file` menjadi `archived`.
- **File Terkait**:
  - `app/Livewire/Registry/Outgoing/Index.php` — Arsip explorer Surat Keluar dengan `saveFinalFile()`.
  - `app/Livewire/Registry/Internal/Index.php` — Arsip explorer Memo Internal dengan `saveFinalFile()`.

### 13. Restrukturisasi Navigasi, Otorisasi & Master Data
- **Sidebar Baru (5 Grup Fungsional)**:
  1. **"Alur Kerja Utama"** — Dashboard.
  2. **"Input Surat"** — Surat Masuk, Surat Keluar, Memo Internal (form registrasi/booking).
  3. **"Kearsipan"** — Arsip Surat Masuk, Arsip Surat Keluar, Arsip Memo Internal (explorer/loker).
  4. **"Manajemen Arsip"** — Klasifikasi, Pemusnahan, Audit Trail.
  5. **"Pengaturan"** — User & Role, Master Data.
- **Penghapusan Grup Workflow**: Grup "Tata Kelola & Workflow" (berisi Verifikasi & Approval, Disposisi) dihapus sepenuhnya dari sidebar.
- **Penyederhanaan Role Editor**:
  - Menghapus *checkbox* modul terkait workflow: Verifikator, Pos Approval, Panel Disposisi.
  - Menghapus baris matriks permission: Verify, Approve, Disposition.
  - Menambahkan kunci modul baru: `input_in_out` (akses input Surat Masuk/Keluar) dan `input_internal` (akses input Memo Internal).
- **Pembatasan Hierarki Klasifikasi**: Klasifikasi Arsip dibatasi maksimal **1 level kedalaman** (Induk → Sub-Induk saja). Dropdown "Induk Klasifikasi" di Master Data hanya menampilkan item root (`whereNull('parent_id')`).
- **Klasifikasi pada Form**: Dropdown klasifikasi pada form Surat Keluar dan Memo Internal hanya menampilkan sub-induk (`whereNotNull('parent_id')`), karena pengarsipan dilakukan pada level sub-klasifikasi.
- **Klasifikasi Dihilangkan dari Surat Masuk**: Field klasifikasi dihapus dari form Surat Masuk karena surat masuk menggunakan penomoran eksternal dari pengirim.
- **Memo Internal Auto-Default**: Form Memo Internal secara otomatis memilih jenis dokumen "Memo Internal" dan menyembunyikan simbol format penomoran dari tampilan dropdown.

### 14. Perbaikan Bug Kritis — Sesi 9 Oktober 2026
- **ParseError pada `ArchiveClassificationIndex.php`**:
  - **Gejala**: Halaman Master Data (`/settings/classifications`) tidak dapat dimuat, menampilkan `ParseError: syntax error, unexpected variable "$allClassifications"` pada baris 153.
  - **Akar Masalah**: Saat skrip pembatasan hierarki klasifikasi dijalankan pada sesi sebelumnya, terjadi duplikasi penulisan variabel akibat *interpolasi variabel PowerShell* di dalam *double-quoted string*. Baris yang seharusnya berbunyi `$allClassifications = Classification::whereNull('parent_id')->get();` malah tertulis `$allClassifications$allClassifications = Classification::whereNull('parent_id')->get();`.
  - **Solusi**: Menghapus duplikasi variabel, mengembalikan sintaks ke bentuk yang benar.
  - **File**: `app/Livewire/Settings/ArchiveClassificationIndex.php:153`
- **Auto-Numbering Mentok di "001" pada Memo Internal & Surat Keluar**:
  - **Gejala**: Setiap kali staf melakukan *Booking Nomor* pada Memo Internal, nomor urut yang dihasilkan selalu `001` dan tidak pernah bertambah (002, 003, dst.), meskipun sudah ada dokumen sebelumnya di *database*.
  - **Akar Masalah**: Pada method `createDocument()` di `app/Livewire/Registry/Internal/Create.php` dan `app/Livewire/Registry/Outgoing/Create.php`, kolom `document_type_id` **tidak disertakan** dalam array `Document::create([...])`. Akibatnya, seluruh dokumen yang tersimpan memiliki `document_type_id = NULL`. Saat mesin *auto-numbering* menghitung nomor urut berikutnya dengan kueri `Document::where('document_type_id', $id)->whereYear(...)->count()`, hasil selalu `0` karena tidak ada dokumen yang cocok — sehingga nomor selalu kembali ke `001`.
  - **Solusi**: Menyuntikkan `'document_type_id' => $this->document_type_id` ke dalam array `Document::create()` pada kedua file Livewire (`Outgoing/Create.php` dan `Internal/Create.php`). Dengan demikian, setiap dokumen yang dibuat kini merekam jenis dokumennya secara akurat, dan penghitungan nomor urut berjalan inkremental dengan benar.
  - **File yang Diperbaiki**:
    - `app/Livewire/Registry/Outgoing/Create.php` — method `createDocument()`
    - `app/Livewire/Registry/Internal/Create.php` — method `createDocument()`

### 15. Antrean Scan Tapersip & Restrukturisasi UI Explorer Arsip (Sesi Lanjutan)
- **Meja Kerja Terpusat "Antrean Scan Tapersip" (`/registry/scan-queue`)**:
  - Dibuat komponen Livewire `App\Livewire\Registry\ScanQueue` untuk mengumpulkan seluruh dokumen berstatus `waiting_for_file` (Surat Keluar & Memo Internal) secara FIFO (First In First Out).
  - Terintegrasi dengan RBAC: modul baru `archive_queue` pada sidebar dan `RoleEditor`.
  - Dilengkapi **Smart Verification Modal** bersistem *Split-Pane* (`max-w-7xl`, `h-[80vh]`, `padding="false"`):
    - **Panel Kiri (450px)**: Identitas dokumen read-only (Nomor, Perihal, Jenis, Tanggal, Pendaftar) dan area drag & drop upload. Tombol simpan terkunci otomatis sampai PDF dipilih.
    - **Panel Kanan (flex-1)**: Instant Live Preview PDF via Alpine.js client-side Blob URL (`URL.createObjectURL()`) dengan `#toolbar=0&navpanes=0`.
  - **Fix Alpine Modal Event**: Meng-upgrade `resources/views/components/ui/modal.blade.php` agar mampu menangani detail event bertipe `Array` dari Livewire 3 (`Array.isArray($event.detail) && $event.detail[0] === id`).
- **Restrukturisasi & Pemadatan Tabel Explorer (Surat Masuk, Surat Keluar, & Memo Internal)**:
  - **Dense UI**: Padding dipadatkan (`py-2.5` pada header, `py-3` pada data baris) agar lebih banyak data tampil per layar.
  - **Unified Container Layout**: Menggantikan desain kartu mengambang lama (`gap-6`) pada Surat Masuk menjadi satu bingkai kontainer terpadu (`h-[calc(100vh-6rem)] rounded-xl border border-slate-200`) yang identik di seluruh modul kearsipan.
  - **5 Kolom Presisi**: Nomor Surat, Perihal Surat, Tanggal Surat & Distribusi, Sifat & Status, Aksi.
  - **No Truncation**: Menghapus `line-clamp-2` dan `truncate` sehingga teks perihal dan pihak distribusi dapat dibaca 100% (*word wrap*).
  - **Pemisahan Distribusi Kontekstual Sesuai Workflow**:
    - **Memo Internal**: Menampilkan `Dari: [Unit Pengirim]` dan `Ke: [Unit Penerima]` (dengan *eager loading* `recipients.recipient` dan pencarian berbasis relasi).
    - **Surat Keluar**: Menampilkan `Dari: [Unit Pengirim]` dan `Kepada: [Instansi/Pihak Eksternal]`.
    - **Surat Masuk**: Menampilkan `Dari: [Instansi/Pihak Pengirim Eksternal]` dan `Kepada: [Unit Penerima Internal]`, lengkap dengan catatan tanggal terima `(Diterima: d M Y)`.
  - **Penghapusan Bilah Tab Status Surat Masuk**: Menghilangkan tab status redundan (*Menunggu Verifikasi, Menunggu Disposisi, Dalam Proses, Perlu Revisi*) karena seluruh alur workflow manual telah ditiadakan; seluruh surat masuk langsung berstatus `Arsip Permanen`.
  - **Pembersihan Kolom Aksi**: Menghapus tombol upload dari tabel arsip (dialihkan secara eksklusif ke Antrean Scan Tapersip). Kolom aksi kini murni berfungsi sebagai peninjau: menampilkan ikon *Lihat Dokumen* jika berkas tersedia, atau tanda tegas **`-`** jika belum ada berkas. Menghapus pula tombol edit revisi yang tidak lagi relevan.
  - **Pembersihan DOM & Controller**: Menghapus modal upload yang tidak lagi terpakai dari view Blade serta membersihkan method/properti upload dari komponen Livewire `Outgoing\Index`, `Internal\Index`, dan mengoptimalkan kueri `Incoming\Index` dengan *eager loading* `currentVersion`.

### 16. Isolasi Loker Penyimpanan (Folder Partitioning by Document Type)
- **Skema Database**: Menambahkan kolom `type` berindeks bertipe `enum('incoming', 'outgoing', 'internal')` dengan nilai default `incoming` pada tabel `folders` (migrasi `2026_10_12_040130_add_type_to_folders_table.php`).
- **Model `App\Models\Folder`**:
  - Kolom `'type'` didaftarkan ke dalam properti `$fillable`.
  - Ditambahkan Local Query Scope `scopeForType($query, string $type)` untuk memfilter kueri folder berdasarkan domain tipe dokumen secara efisien dan konsisten.
- **Isolasi pada Komponen Livewire Explorer (`Index.php`)**:
  - **Surat Masuk (`Incoming\Index`)**: Mengunci kueri pohon loker (`$folders`), daftar induk loker (`$allFolders`), pembuatan loker baru (`'type' => 'incoming'`), dan penghapusan loker secara ketat hanya untuk domain `incoming`.
  - **Surat Keluar (`Outgoing\Index`)**: Mengunci kueri pohon loker, opsi induk loker, dan pembuatan loker baru secara ketat untuk domain `outgoing`.
  - **Memo Internal (`Internal\Index`)**: Mengunci kueri pohon loker, opsi induk loker, dan pembuatan loker baru secara ketat untuk domain `internal`.
- **Isolasi pada Formulir Registrasi & Edit Dokumen**:
  - `Incoming\Create` & `Incoming\Edit`: Dropdown loker hanya menampilkan folder bertipe `incoming` (`Folder::forType('incoming')->get()`).
  - `Outgoing\Create`: Dropdown loker hanya menampilkan folder bertipe `outgoing` (`Folder::forType('outgoing')->get()`).
  - `Internal\Create`: Dropdown loker hanya menampilkan folder bertipe `internal` (`Folder::forType('internal')->get()`).
  - Mencegah berkas salah dimasukkan ke dalam loker milik modul kearsipan lainnya.
- **Penyelarasan UI Modal Slide-Over**:
  - Judul dan teks deskripsi modal pembuatan loker baru disesuaikan secara kontekstual di ketiga halaman:
    - Surat Masuk: *"Tambah Loker Surat Masuk"* ("Loker ini khusus untuk mengelompokkan berkas Surat Masuk.")
    - Surat Keluar: *"Tambah Loker Surat Keluar"* ("Loker ini khusus untuk mengelompokkan berkas Surat Keluar.")
    - Memo Internal: *"Tambah Loker Memo Internal"* ("Loker ini khusus untuk mengelompokkan berkas Memo Internal.")
  - Dropdown "Induk Loker" hanya menampilkan daftar folder dari kategori yang sama sehingga pohon hierarki antar-modul tidak akan pernah bercampur.

### 17. Standardisasi Penamaan Berkas Arsip Otomatis (Archive File Naming Engine)
- **Format Standar**: `{Nomor Surat}_{YYYY-MM-DD}.pdf`
  - Karakter garis miring (`/` dan `\`) pada nomor surat otomatis diubah menjadi **spasi tunggal** (` `).
  - Karakter ilegal sistem operasi (`:`, `*`, `?`, `"`, `<`, `>`, `|`) dibersihkan.
  - Spasi ganda dinormalisasi menjadi satu spasi bersih.
  - Tanggal surat diformat ke standar ISO `YYYY-MM-DD` (contoh: `2026-10-12`).
  - Contoh: Dokumen bernomor `001/M-IT/10/2026` bertanggal `12 Oktober 2026` disimpan dengan nama **`001 M-IT 10 2026_2026-10-12.pdf`**.
  - Fallback untuk dokumen tanpa nomor: `DOKUMEN {id}_{YYYY-MM-DD}.pdf`.
  - Multi-versi (> 1.0) secara otomatis ditambahkan sufiks `_v{versi}` (contoh: `001 M-IT 10 2026_2026-10-12_v2.0.pdf`).
- **Implementasi Terpusat di Model `Document`**:
  - Dibuat method `generateArchiveFileName(string $extension = 'pdf', ?string $version = null): string` pada `app/Models/Document.php`.
- **Penerapan pada Seluruh Titik Simpan Berkas (*Storage Points*)**:
  - `ScanQueue::saveFinalFile()`: Mengganti `$file->store()` acak menjadi `$file->storeAs('documents', $fileName, 'public')` saat Tapersip mengarsipkan dokumen final hasil scan.
  - `Incoming\Create::handleFileAndVersion()`: Menerapkan penamaan terstandarisasi saat mengarsipkan surat masuk.
  - `Outgoing\Create::handleFileAndVersion()`: Menerapkan penamaan terstandarisasi saat membuat surat keluar langsung dengan berkas.
  - `Internal\Create::handleFileAndVersion()`: Menerapkan penamaan terstandarisasi saat membuat memo internal langsung dengan berkas.
  - `Incoming\Edit`: Menerapkan penamaan terstandarisasi pada revisi berkas dengan pelacakan nomor versi dinamis.
- **Penyelarasan Header Unduhan / Streaming PDF**:
  - Route `/document/preview/{version}` pada `routes/web.php` diperbarui untuk menyuntikkan header `Content-Disposition: inline; filename="{StandardFileName}"`, sehingga ketika pengguna mengklik unduh pada browser PDF viewer, nama berkas yang tersimpan di laptop pengguna otomatis bernama resmi.

### 18. Kontrol Hak Akses Form & Digitalisasi Arsip Langsung (Direct Archiving & Role-Gated Form Actions)
- **Pembaruan Matriks Peran (`resources/views/livewire/settings/role-editor.blade.php`)**:
  - Izin aksi `archive` diperjelas menjadi **"Arsip & Upload Langsung"** dengan deskripsi *"Dapat mengunggah file fisik, melakukan arsip langsung, dan mengelola retensi arsip."*
  - Opsi toggle `rahasia` pada baris `archive` diaktifkan sepenuhnya (tidak lagi dinonaktifkan/disabled), memungkinkan peran tertentu (seperti Tapersip/Direksi) mengarsipkan berkas rahasia secara sah.
- **Dua Alur Aksi Mandiri pada Formulir Surat Keluar & Memo Internal**:
  - **`bookNumber()` (Staf Biasa)**:
    - Hanya memvalidasi metadata tanpa mewajibkan lampiran file PDF.
    - Menghasilkan nomor surat resmi otomatis dan membuat dokumen berstatus `waiting_for_file`.
    - Menampilkan pop-up modal "Berhasil Booking Nomor" agar staf menyalin nomor resmi.
  - **`saveAndArchive()` (Khusus Role dengan Izin `archive`)**:
    - Memvalidasi metadata dan mewajibkan lampiran file PDF (`mimes:pdf|max:10240`).
    - Memverifikasi otorisasi di sisi backend: `Auth::user()->hasDocumentPermission('archive', $sifat)`.
    - Menerbitkan nomor resmi dan menyimpan berkas terstandarisasi `{Nomor Surat}_{YYYY-MM-DD}.pdf`.
    - Membuat `DocumentVersion` (1.0) dan langsung menetapkan status dokumen menjadi `archived` (Arsip Permanen).
    - Melakukan redirect ke indeks kearsipan dengan pesan kilat (*flash message*) sukses.
- **Penyembunyian Bersyarat pada Panel Pratinjau Dokumen (`Outgoing\Create` & `Internal\Create`)**:
  - **Jika User Memiliki Hak `archive`**: Panel kanan menampilkan area *Drag & Drop* PDF interaktif dengan pratinjau instan (*Live Preview*), serta memunculkan tombol **"Simpan & Arsipkan"** berwarna hijau di bawah formulir.
  - **Jika User TIDAK Memiliki Hak `archive` (Staf Biasa)**: Area upload PDF disembunyikan sepenuhnya! Sebagai gantinya, panel kanan menampilkan **"Panduan Alur Booking Nomor Surat"** (Langkah 1 s/d 4) yang elegan dan profesional. Tombol aksi hanya menampilkan satu pilihan: **"Ambil Nomor Surat"**.
- **Perlindungan Hak Akses Pratinjau Dokumen (*View Permission Enforcement*)**:
  - Pada rute `/document/preview/{version}` di `routes/web.php`, ditambahkan gerbang otorisasi backend: memblokir akses dengan kode `403 Forbidden` jika pengguna tidak memiliki izin `view` untuk sifat dokumen tersebut (`biasa`, `penting`, atau `rahasia`).
  - Pada tabel indeks kearsipan (`Surat Masuk`, `Surat Keluar`, dan `Memo Internal`), ikon aksi mata *"Lihat Dokumen"* dikontrol oleh `hasDocumentPermission('view', $doc->sifat)`:
    - Jika berizin: Menampilkan tombol biru interaktif untuk membuka dokumen.
    - Jika dokumen ada namun user tidak memiliki izin: Menampilkan ikon gembok terkunci (*disabled*) dengan tooltip `"Akses Dibatasi: Memerlukan izin melihat dokumen [Sifat]"`.
    - Jika belum ada berkas: Menampilkan tanda **`-`**.

### 19. Penggabungan Hak Akses Loker Penyimpanan ke Izin "Arsip & Upload Langsung" (Folder Assignment RBAC)
- **Penyembunyian Bersyarat Dropdown Loker pada Form Staf (`Outgoing\Create` & `Internal\Create`)**:
  - Dropdown "Loker Penyimpanan" pada formulir input Surat Keluar dan Memo Internal dibungkus dengan kondisi `@if($this->canArchive)`.
  - **Bagi Staf Biasa (Tanpa Izin `archive`)**: Dropdown loker tidak muncul sama sekali di formulir. Staf tidak dibebani memilih lemari/map arsip fisik. Saat mengambil nomor (`bookNumber()`), nilai `folder_id` otomatis diset `null`.
  - **Bagi Admin / Tapersip (Memiliki Izin `archive`)**: Dropdown loker tetap muncul, memungkinkan penempatan loker langsung saat mendigitalkan dokumen lama via tombol **"Simpan & Arsipkan"**.
- **Pemilihan Loker di Antrean Scan Tapersip (`ScanQueue.php` & `scan-queue.blade.php`)**:
  - Karena staf biasa tidak menentukan loker saat booking nomor, tugas penempatan loker diserahkan kepada Tapersip saat berkas fisik diterima.
  - Pada panel kiri modal *Antrean Scan Tapersip*, ditambahkan dropdown **"Loker Penyimpanan"** dinamis yang terisolasi sesuai tipe dokumen (`Folder::forType($doc->type)`).
  - Ketika Tapersip mengunggah berkas scan dan menekan tombol simpan, sistem secara otomatis memperbarui kolom `folder_id` pada dokumen tersebut.
- **Pembaruan Deskripsi Matriks Peran (`resources/views/livewire/settings/role-editor.blade.php`)**:
  - Deskripsi izin `archive` diperbarui menjadi: *"Dapat mengunggah file fisik, menentukan loker penyimpanan, melakukan arsip langsung, dan mengelola retensi arsip."* guna merefleksikan penggabungan wewenang penempatan loker ke dalam izin tersebut.

### 20. Redesain Dashboard: Monitoring Arsip Dokumen Personal & Card Antrean Ke-5
- **Penambahan Card ke-5 pada Bento Grid Stats (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-5`)**:
  - Menambahkan kartu metrik baru bertema Amber/Kuning: **"Menunggu Upload"** (`$countWaiting`).
  - Menghitung jumlah dokumen yang masih berstatus `waiting_for_file`.
  - **Bersifat Personal**:
    - Bagi staf biasa: Hanya menghitung dokumen yang dibuat oleh staf tersebut (`where('created_by', Auth::id())`), dengan keterangan *"Surat Anda antre scan"*.
    - Bagi Superadmin: Menghitung seluruh antrean kantor yang harus diproses oleh Tapersip, dengan keterangan *"Antrean scan kantor"*.
- **Tabel "Monitoring Arsip Dokumen" (Pengganti Arsip Dokumen Terbaru)**:
  - **Scoping Mandiri (Personal per Akun)**: Staf A hanya melihat naskah surat miliknya sendiri. Staf B hanya melihat miliknya sendiri (`where('created_by', Auth::id())`). Superadmin dapat memantau seluruh naskah sistem.
  - Terdapat indikator badge visual di header tabel: *"Naskah Surat Anda"* (untuk staf) atau *"Seluruh Naskah Sistem"* (untuk Superadmin).
  - **4 Kolom Presisi (Kolom Berkas Ditiadakan 100%)**:
    1. **Nomor & Jenis Dokumen**: Nomor surat resmi (`font-mono text-[13px] font-bold`) beserta badge jenis (*Surat Masuk*, *Surat Keluar*, *Memo Internal*).
    2. **Perihal & Tujuan**: Teks perihal yang mengalir bebas (*word wrap*) dan nama penerima/tujuan surat (`Kepada: ...`).
    3. **Waktu Pengambilan**: Tanggal entry surat serta waktu relatif (contoh: *15 menit yang lalu* atau *Kemarin*).
    4. **Status Monitoring**:
       - `waiting_for_file`: Badge Amber berikon jam pasir **`⏳ Menunggu Upload`** (memberi tahu staf bahwa fisik surat belum discan/diunggah Tapersip).
       - `archived`: Badge Emerald berikon centang **`✓ Selesai Diarsipkan`** (memberi tahu staf bahwa berkas sudah resmi tersimpan di repositori digital).
  - Empty state ramah staf: *"Belum ada naskah surat yang Anda daftarkan."* lengkap dengan arahan untuk mendaftarkan surat di menu Input Surat.

### 21. Paginasi 5 Data & Aturan 30 Hari pada Monitoring Dashboard (30-Day Auto-Prune & 5-Row Pagination)
- **Logika Batas Waktu 30 Hari (*30-Day Auto-Prune*) pada Tabel Monitoring**:
  - Diterapkan *smart time-window* pada kueri `App\Livewire\Dashboard`:
    - Dokumen yang berstatus `waiting_for_file` (Menunggu Upload) **selalu ditampilkan tanpa batas waktu** agar berkas fisik yang belum diserahkan ke Tapersip tidak pernah terlewat atau terlupakan dari radar pantauan staf.
    - Dokumen yang berstatus `archived` (Selesai Diarsipkan) **hanya ditampilkan jika selesai diarsipkan dalam kurun waktu 30 hari terakhir** (`updated_at >= now()->subDays(30)`).
    - Setelah melewati 30 hari, dokumen yang sudah selesai akan otomatis "dibersihkan" dari meja pantau dashboard agar antarmuka staf tetap rapi dan hanya berfokus pada pekerjaan aktif.
    - Dokumen yang hilang dari dashboard tetap aman, abadi, dan dapat dicari kapan saja pada menu repositori utama Kearsipan.
- **Paginasi 5 Data per Halaman (`Livewire\WithPagination`)**:
  - Tabel monitoring dibatasi tepat 5 baris data per tampilan dengan paginasi Livewire:
    `$monitoringQuery->orderBy('created_at', 'desc')->paginate(5, ['*'], 'monitoringPage');`
  - Penamaan halaman khusus `'monitoringPage'` mencegah bentrok parameter URL dengan komponen lain.
  - Ditambahkan bilah navigasi paginasi interaktif pada footer kartu tabel (`$monitoringDocuments->links()`) dengan tombol nomor halaman (`< 1 2 3 >`) yang mulus tanpa refresh halaman penuh (*SPA-style*).
  - Menjaga ketinggian kartu tabel tetap simetris dan seimbang dengan kartu Log Aktivitas Sistem di sebelah kanan.

### 22. Desain Custom Pagination Enterprise: Tombol Mandiri & Pemisahan Ruang Lega (Tangible Button Pagination)
- **Pembuatan Template Paginasi Kustom (`resources/views/vendor/pagination/custom.blade.php`)**:
  - Menggantikan template paginasi default Laravel yang conjoined/berhimpitan dengan desain tombol fisik mandiri (*tangible buttons*).
  - **Pemisahan Ruang Lega**:
    - **Sisi Kiri**: Teks counter Bahasa Indonesia yang rapi: *"Menampilkan X – Y dari Z naskah"*.
    - **Sisi Kanan**: Kelompok tombol navigasi mandiri dengan jarak renggang (`gap-1.5`), dipisahkan secara fleksibel menggunakan `justify-between` sehingga tidak akan pernah berhimpitan.
  - **Desain Tombol Nyata (Tangible Button Design)**:
    - **Tombol Prev & Next**: Kotak rounded (`rounded-lg`), berlatar putih dengan border tegas (`border border-slate-200 shadow-2xs`), serta feedback klik abu-abu yang nyata (`active:bg-slate-200 hover:bg-slate-100 hover:border-slate-300`).
    - **Nomor Halaman Aktif**: Tombol kotak mandiri (`w-8 h-8 rounded-lg font-bold`) berlatar warna tema utama instansi (`bg-[var(--color-primary)] text-white shadow-xs`).
    - **Nomor Halaman Lainnya**: Tombol kotak mandiri berlatar putih dengan border halus yang berubah menjadi abu-abu solid saat diklik/hover (`hover:bg-slate-100 active:bg-slate-200`).
    - **Status Disabled**: Tombol panah yang tidak aktif (misal di halaman pertama) meredup halus (`opacity-50 cursor-not-allowed bg-slate-50 text-slate-400`).
  - Mendukung Livewire 3 pagination secara penuh tanpa me-refresh halaman (*SPA-style*).
- **Penerapan pada Dashboard**:
  - Diaktifkan pada tabel Monitoring Arsip Dokumen melalui `{{ $monitoringDocuments->links('vendor.pagination.custom') }}`.

### 23. Standardisasi Custom Pagination pada Seluruh Modul Kearsipan (Incoming, Outgoing, Internal, & Scan Queue)
- **Batas Tampilan 10 Data per Halaman**:
  - Seluruh modul penelusuran kearsipan dikonfirmasi dan dikunci untuk menampilkan tepat **10 data per halaman**:
    - `Incoming\Index.php` (`paginate(10)`)
    - `Outgoing\Index.php` (`paginate(10)`)
    - `Internal\Index.php` (`paginate(10)`)
    - `ScanQueue.php` (`paginate(10)`)
- **Penerapan Template Paginasi Kustom (`vendor.pagination.custom`)**:
  - Diimplementasikan pada seluruh view tabel kearsipan:
    - `resources/views/livewire/registry/incoming/index.blade.php`
    - `resources/views/livewire/registry/outgoing/index.blade.php`
    - `resources/views/livewire/registry/internal/index.blade.php`
    - `resources/views/livewire/registry/scan-queue.blade.php`
  - Seluruh tabel kini memiliki navigasi tombol mandiri (`gap-1.5`), efek klik abu-abu interaktif, dan teks counter Bahasa Indonesia yang lega tanpa berhimpitan.
- **Konfigurasi Global Default View (`AppServiceProvider.php`)**:
  - Mendaftarkan `Paginator::defaultView('vendor.pagination.custom')` pada metode `boot()` di `App\Providers\AppServiceProvider.php`.
  - Memastikan seluruh fitur paginasi di masa depan (termasuk User, Role, Klasifikasi, Audit Trail) secara otomatis menggunakan template tombol kustom yang seragam tanpa perlu konfigurasi manual berulang.

### 24. RBAC Kartu Statistik Dashboard (Card-Level Visibility via Role Matrix)
- **Konfigurasi Hak Akses Granular di Role Editor (`role-editor.blade.php`)**:
  - Menambahkan grup ke-4 pada blok *Visibilitas Modul*: **"Kartu Statistik Dashboard"** (`card_incoming`, `card_outgoing`, `card_internal`, `card_waiting`, `card_retention`).
  - Mengisi kolom ke-4 grid formulir peran yang sebelumnya kosong, menghasilkan tata letak 4 kolom yang simetris dan seimbang.
  - Admin/Superadmin dapat mencentang kartu mana saja yang boleh dilihat oleh masing-masing peran (Staf Biasa, Tapersip, Manajer, dll.).
  - Tersimpan otomatis ke dalam array JSON `module_visibility` pada tabel `roles` tanpa memerlukan migrasi database baru.
- **Logika Otorisasi & Tata Letak Adaptif di Backend (`Dashboard.php`)**:
  - Membaca hak akses pengguna menggunakan metode `$user->hasModuleAccess('card_...')` yang sudah ada (Superadmin otomatis bypass semua kartu).
  - Menghitung jumlah kartu yang berhak dilihat (`$visibleCardsCount`).
  - Menghitung kelas grid CSS dinamis (`$gridColsClass`):
    - 5 kartu aktif ➔ `lg:grid-cols-5`
    - 4 kartu aktif ➔ `lg:grid-cols-4`
    - 3 kartu aktif ➔ `lg:grid-cols-3`
    - 2 kartu aktif ➔ `sm:grid-cols-2`
    - 1 kartu aktif ➔ `grid-cols-1 max-w-sm`
    - 0 kartu aktif ➔ Kontainer kartu disembunyikan sepenuhnya.
- **Penerapan Tampilan Bersyarat pada Dashboard (`dashboard.blade.php`)**:
  - Kontainer Bento Grid dibungkus dengan `@if($visibleCardsCount > 0)` dan menerapkan kelas grid dinamis `$gridColsClass`.
  - Setiap kartu (Total Surat Masuk, Total Surat Keluar, Total Memo Internal, Menunggu Upload, Peringatan JRA) dibungkus secara independen dengan pengaman `@if($canView...)`.
  - Menjamin kartu tidak meninggalkan celah kosong pada layar saat peran tertentu hanya diizinkan melihat sebagian kartu.

### 25. Pemisahan Hak Akses Modul Input Surat Masuk & Keluar (Granular Input Permissions)
- **Pemisahan Kunci Modul Navigasi (`role-editor.blade.php`)**:
  - Kunci gabungan legacy `input_in_out` dipecah menjadi dua izin mandiri:
    - `input_incoming`: **Input Surat Masuk**
    - `input_outgoing`: **Input Surat Keluar**
  - Bersama dengan `input_internal` (**Input Memo Internal**), kini setiap fitur input naskah memiliki hak akses mandiri yang dapat diatur secara terpisah per peran.
- **Penyelarasan Alur Backend & Route Middleware (`routes/web.php`)**:
  - Route `/registry/incoming/create` diproteksi khusus oleh middleware `module:input_incoming`.
  - Route `/registry/outgoing/create` diproteksi khusus oleh middleware `module:input_outgoing`.
  - Route `/registry/internal/create` diproteksi khusus oleh middleware `module:input_internal`.
- **Backward Compatibility Graceful Fallback (`User.php` & `RoleEditor.php`)**:
  - Pada `app/Models/User.php`: Metode `hasModuleAccess()` ditambahkan logika fallback otomatis; jika suatu peran di database masih menyimpan kunci lama `input_in_out`, sistem secara cerdas tetap mengizinkan akses ke `input_incoming` dan `input_outgoing` sehingga tidak ada staf yang terkunci saat masa transisi.
  - Pada `app/Livewire/Settings/RoleEditor.php`: Di dalam metode `mount()`, peran yang memiliki `input_in_out` secara otomatis mengaktifkan kedua checklist baru saat halaman konfigurasi dibuka oleh admin.
- **Penyelarasan Tampilan Sidebar (`components/layouts/app.blade.php`)**:
  - Kategori "Input Surat" diperbarui pengamannya: hanya tampil jika pengguna memiliki salah satu dari izin `input_incoming`, `input_outgoing`, atau `input_internal`.
  - Masing-masing menu tautan (Surat Masuk, Surat Keluar, Memo Internal) dibungkus dengan pengecekan izin mandiri. Jika staf hanya diberi izin "Input Surat Keluar", maka menu "Surat Masuk" otomatis hilang dari sidebar.

### 26. Sirkulasi Peminjaman Berkas Fisik & Cetak Tiket Hardcopy (Physical Document Circulation Engine)
- **Skema Database & Model `DocumentLoan` (`document_loans`)**:
  - Migrasi `2026_10_12_094015_create_document_loans_table.php` mendefinisikan tabel sirkulasi dengan atribut:
    - `loan_code`: Kode unik tiket peminjaman dengan format `TIKET-YYYYMM-XXXX` (dihasilkan otomatis oleh method `DocumentLoan::generateTicketCode()`).
    - `document_id`: Foreign key ke tabel `documents` (cascade on delete).
    - `user_id`: Foreign key ke tabel `users` (peminjam berkas fisik).
    - `officer_id`: Foreign key nullable ke tabel `users` (petugas Tapersip yang menyerahkan/menerima berkas).
    - `borrow_date` & `expected_return_date`: Tanggal mulai pinjam dan estimasi batas waktu pengembalian.
    - `returned_date`: Tanggal aktual saat fisik dikembalikan ke lemari loker.
    - `purpose`: Keperluan peminjaman fisik (audit, klaim hukum, tender, dsb.).
    - `notes`: Catatan kondisi fisik berkas saat dikembalikan.
    - `status`: Enum status siklus peminjaman (`requested`, `borrowed`, `returned`, `rejected`).
  - Relasi Eloquent pada Model `Document`:
    - `loans()`: `hasMany(DocumentLoan::class)`
    - `activeLoan()`: `hasOne(DocumentLoan::class)->whereIn('status', ['requested', 'borrowed'])->latestOfMany()`
    - `isCurrentlyBorrowed()`: Helper boolean untuk mengecek ketersediaan berkas hardcopy di Tapersip.
- **Workflow Sirkulasi 4 Langkah (Physical Circulation Flow)**:
  1. **Pencarian (Staf)**: Staf menelusuri katalog dokumen yang sah diarsipkan (`status = 'archived'`) menggunakan filter Kata Kunci, Sifat Dokumen, Rentang Tanggal, dan Jenis Naskah.
  2. **Pengajuan & Cetak Tiket (Staf)**: Staf mengklik "Ajukan Pinjam" pada berkas yang berstatus Tersedia, mengisi tanggal & keperluan. Sistem menerbitkan tiket (`requested`) dan membuka modal tiket cetak.
  3. **Serah Terima Fisik (Petugas Tapersip)**: Staf menyerahkan lembar tiket cetak ke meja Tapersip. Petugas mengecek kode tiket pada Tab Sirkulasi Tapersip, mengambil naskah dari loker fisik (`folders.name`), dan menekan tombol **"Serahkan Berkas"** (status berubah menjadi `borrowed`). Berkas otomatis terkunci bagi peminjam lain.
  4. **Pengembalian Berkas (Petugas Tapersip)**: Staf mengembalikan fisik naskah ke Tapersip. Petugas memeriksa kondisi fisik dan menekan **"Terima & Masukkan Loker"**, mengisi catatan fisik opsional, dan mengubah status menjadi `returned` (berkas kembali Tersedia).
- **Komponen Livewire Terpadu (`App\Livewire\Registry\DocumentLoanIndex`)**:
  - Didesain dengan 3 tab mandiri:
    - **Tab 1: "Katalog & Cari Surat"**: Terbuka untuk seluruh staf, memuat filter pencarian komprehensif, menampilkan lokasi loker fisik naskah (`folders.name`), dan indikator status ketersediaan (`✓ Tersedia di Loker` vs `⏳ Sedang Dipinjam [Nama Peminjam]`).
    - **Tab 2: "Pinjaman Saya"**: Area personal staf untuk memantau status fisik seluruh berkas yang diajukannya, dilengkapi tombol **"Cetak Ulang Tiket"**.
    - **Tab 3: "Sirkulasi & Persetujuan Tapersip"**: Meja kerja operasional Tapersip (diproteksi hak akses arsip/superadmin). Dilengkapi pencarian cepat kode tiket/nama peminjam, filter status sirkulasi, kolom rincian **Keperluan Pinjam** (memuat teks keperluan pemohon dan riwayat catatan kondisi fisik pengembalian), tombol satu-klik **"Serahkan Berkas"**, modal catatan kondisi fisik pengembalian, dan opsi penolakan.
- **Modal Tiket Hardcopy Siap Cetak (Print-Friendly Layout)**:
  - Dirancang sesuai standar dokumen kedinasan: Header E-Arsip, Judul Dokumen, Badge Nomor Tiket, Identitas Pemohon (Nama, Jabatan, Unit Kerja), Metadata Naskah (Nomor, Tanggal, Sifat, Perihal), Kotak Highlight Tebal Lokasi Loker Fisik (`📁 Loker: ...`), Rincian Batas Waktu Pinjam, dan 2 Kolom Tanda Tangan Basah (Pemohon & Petugas Layanan Tapersip).
  - Dilengkapi isolasi CSS `@media print` sehingga saat tombol "Cetak Tiket (Print)" diklik (`window.print()`), hanya lembar tiket `#printable-ticket` yang dicetak ke kertas A4 dengan bersih dan tajam, sementara seluruh elemen antarmuka web disembunyikan otomatis.
- **Integrasi RBAC & Routing**:
  - Rute `/borrowings` didaftarkan pada `routes/web.php` dan dilindungi middleware `module:document_borrowing`.
  - Sidebar layout utama (`app.blade.php`) menyematkan menu **"Cari & Pinjam Surat"** di bawah kategori "Kearsipan" berpagar `@if(auth()->user()->hasModuleAccess('document_borrowing'))`.
  - Formulir konfigurasi peran (`role-editor.blade.php`) dilengkapi checklist baru `document_borrowing` agar Superadmin dapat mengatur visibilitas menu ini ke setiap jabatan/role.
- **Kontrol Hak Akses Tab Sirkulasi pada Kelompok Manajemen Kepatuhan (`loan_circulation`)**:
  - Pada formulir Role Editor (`role-editor.blade.php`) di bawah **Kelompok 2: Manajemen Kepatuhan**, ditambahkan checklist baru: **"Sirkulasi & Persetujuan Tapersip"** (`loan_circulation`).
  - Tab 3 ("Sirkulasi & Persetujuan Tapersip") pada `DocumentLoanIndex` dikunci secara granular hanya untuk peran yang dicentang `loan_circulation` (atau Superadmin).
  - Staf biasa yang membuka menu Cari & Pinjam Surat hanya melihat 2 tab (Katalog dan Pinjaman Saya), sedangkan Tab 3 disembunyikan sepenuhnya.
  - Upaya manipulasi atau eksekusi aksi approval/return oleh akun tanpa izin otomatis diblokir dengan respon `403 Forbidden`.
  - Teruji otomatis melalui suite pengujian `DocumentLoanTest` dengan 5 uji skenario dan 23 asersi lulus (*PASS*).

### 27. Fitur Ekspansi Teks Perihal (Show More / Show Less) pada Halaman Cari & Pinjam Surat
- **Implementasi Interaktif Alpine.js pada Seluruh Tabel `/borrowings`**:
  - Diterapkan pada 3 lokasi tabel di `resources/views/livewire/registry/document-loan-index.blade.php`:
    1. **Tab 1: Katalog & Cari Surat** (Kolom Perihal & Distribusi).
    2. **Tab 2: Pinjaman Saya** (Kolom Dokumen & Lokasi Fisik).
    3. **Tab 3: Sirkulasi & Persetujuan Tapersip** (Kolom Dokumen & Loker Fisik).
  - **Mekanisme Kerja**:
    - **Tampilan Awal**: Teks perihal dibatasi maksimal 2 baris (`line-clamp-2`) agar tata letak tabel tetap padat, rapi, dan seragam.
    - **Deteksi Otomatis Teks Panjang**: Jika teks perihal melebihi batas 75 karakter (`mb_strlen > 75`), sistem memunculkan tombol teks interaktif: **`Show More ▾`**.
    - **Ekspansi Instan (Client-Side 0ms)**: Menggunakan state lokal Alpine.js (`x-data="{ expanded: false }"`), saat tombol diklik, teks perihal langsung terbuka melebar secara penuh tanpa ada kata yang terpotong (`whitespace-normal break-words`).
    - **Penciutan Fleksibel**: Tombol otomatis bertransformasi menjadi **`Show Less ▴`** untuk menciutkan kembali teks ke 2 baris.
    - **Perihal Pendek**: Jika teks perihal ringkas (<= 75 karakter), tombol otomatis disembunyikan sehingga antarmuka tetap bersih (*clean*).
  - **Verifikasi Pengujian**: Seluruh suite pengujian otomatis `DocumentLoanTest` (5 *test cases*, 23 *assertions*) terkonfirmasi tetap lulus 100% (*PASS*).

### 28. Dropdown Menu Tindakan Petugas pada Tab Sirkulasi Tapersip
- **Penggantian Deretan Tombol Menjadi Dropdown Ramping (`document-loan-index.blade.php`)**:
  - Kolom **Tindakan Petugas** pada Tab 3 ("Sirkulasi & Persetujuan Tapersip") yang sebelumnya memuat tombol-tombol dan ikon terpisah dirampingkan menjadi satu tombol pemicu seragam: **`Pilihan Aksi ▾`** (`rounded-lg border border-slate-200 bg-white text-slate-700`).
  - Menjaga ketinggian baris tabel 100% simetris, padat, dan seragam di semua status peminjaman.
- **Desain Opsi Teks Murni Tanpa Ikon (Clean Typography)**:
  - Sesuai instruksi khusus, menu pop-up dropdown didesain dengan tipografi bersih **tanpa ikon pada setiap teks opsinya**:
    - **Semua Status**: Opsi *"Lihat / Cetak Tiket"* (`text-slate-700`).
    - **Status `requested` (Menunggu Serah Terima)**:
      - *"Serahkan Berkas (Pinjamkan)"* (`text-emerald-700 font-semibold`) dengan konfirmasi serah terima fisik.
      - *"Tolak Permohonan"* (`text-red-600 font-semibold`) dengan konfirmasi penolakan.
    - **Status `borrowed` (Sedang Dipinjam Staf)**:
      - *"Terima & Masukkan Loker"* (`text-blue-700 font-semibold`) membuka modal konfirmasi pengembalian dan pencatatan kondisi berkas.
- **Pencegahan Overflow & Auto-Close**:
  - Dropdown dikontrol oleh Alpine.js (`x-data="{ open: false }"` dan `@click.outside="open = false"`).
  - Dilengkapi z-index tinggi (`z-30`) dan bayangan lembut (`shadow-xl`) untuk memastikan menu melayang di atas baris tabel tanpa terpotong batas container.
- **Verifikasi Pengujian**:
  - Seluruh suite pengujian otomatis `DocumentLoanTest` (5 *test cases*, 23 *assertions*) terkonfirmasi lulus 100% (*PASS*).

### 29. Pembersihan Ikon Status pada Seluruh Tab Halaman Cari & Pinjam Surat (Clean Status Badges)
- **Penghapusan Seluruh Ikon/Simbol pada Kolom Status (`document-loan-index.blade.php`)**:
  - Seluruh ikon SVG (spinner, centang, ping dot) dan simbol teks (`✓`) yang sebelumnya mendampingi teks status pada ketiga tab telah dihapus bersih demi mencapai tampilan minimalis dan *clean*:
    1. **Tab 1: Katalog & Cari Surat** (Kolom Status Fisik):
       - Menghapus ikon SVG spinner pada status `Dipinjam`.
       - Menghapus bulatan dot biru pada status `Diajukan Pinjam`.
       - Menghapus ikon SVG centang hijau pada status `Tersedia`.
    2. **Tab 2: Pinjaman Saya** (Kolom Status):
       - Menghapus bulatan dot ping biru pada status `Menunggu`.
       - Menghapus bulatan dot amber pada status `Sedang Dipinjam`.
       - Menghapus ikon SVG centang hijau pada status `Sudah Kembali`.
    3. **Tab 3: Sirkulasi & Persetujuan Tapersip** (Kolom Status):
       - Menghapus simbol `✓ ` pada status `Sudah di Loker`.
  - **Hasil Visual**: Badge status kini tampil murni menggunakan teks dengan kontras warna yang tegas, rapi, dan konsisten di seluruh tabel.
- **Verifikasi Pengujian**:
  - Suite pengujian otomatis `DocumentLoanTest` (5 *test cases*, 23 *assertions*) terkonfirmasi tetap lulus 100% (*PASS*).

### 30. Penutupan Tiket Peminjaman (Closed Ticket Behavior: Status Returned & Rejected Berubah Menjadi '-')
- **Mekanisme Siklus Akhir Peminjaman (*Terminal State Lifecycle*)**:
  - Pada halaman `/borrowings` (`document-loan-index.blade.php`), dokumen yang telah mencapai status final:
    - `returned`: Berkas fisik sudah diterima dan resmi dikembalikan ke dalam loker arsip ("Sudah Kembali" di Tab 2 / "Sudah di Loker" di Tab 3).
    - `rejected`: Permohonan peminjaman telah ditolak oleh Tapersip ("Ditolak Tapersip" di Tab 2 / "Ditolak" di Tab 3).
  - Pada kedua status tersebut, tiket peminjaman dianggap **CLOSED (Ditutup)**.
- **Transformasi Kolom Aksi Menjadi Tanda Strip (`-`)**:
  - **Tab 2: Pinjaman Saya (Kolom Aksi)**:
    - Status Aktif (`requested` atau `borrowed`): Tetap menampilkan tombol cetak/buka tiket peminjaman.
    - Status Closed (`returned` atau `rejected`): Tombol cetak tiket otomatis hilang dan digantikan tanda strip abu-abu tegas: `<span class="text-slate-400 font-mono text-sm inline-block select-none pr-3">-</span>`.
  - **Tab 3: Sirkulasi & Persetujuan Tapersip (Kolom Tindakan Petugas)**:
    - Status Aktif (`requested` atau `borrowed`): Tetap menampilkan dropdown `Pilihan Aksi ▾` untuk penyerahan fisik atau penerimaan pengembalian ke loker.
    - Status Closed (`returned` atau `rejected`): Seluruh menu dropdown aksi otomatis hilang dan digantikan tanda strip abu-abu tegas: `<span class="text-slate-400 font-mono text-sm inline-block select-none pr-3">-</span>`.
  - Menghilangkan kebingungan petugas Tapersip dan staf, memastikan mata petugas hanya berfokus pada baris yang masih memerlukan penanganan fisik aktif.
- **Verifikasi Pengujian Otomatis**:
  - Pengujian `DocumentLoanTest::test_officer_can_approve_and_return_loan` diperluas untuk memastikan kolom tindakan petugas berubah menjadi tanda `-` segera setelah berkas dikembalikan ke loker.
  - Seluruh suite pengujian otomatis (5 *test cases*, 24 *assertions*) terkonfirmasi lulus 100% (*PASS*).

### 31. Standardisasi Tombol Show More / Show Less pada Kolom Perihal di Dashboard & Seluruh Modul Kearsipan
- **Penerapan Luas Komponen Ekspansi Teks (*Text Truncation & Expansion*)**:
  - Diterapkan secara seragam menggunakan Alpine.js pada seluruh tabel utama aplikasi:
    1. **Dashboard Monitoring** (`resources/views/livewire/dashboard.blade.php`): Kolom *Perihal & Tujuan*.
    2. **Arsip Surat Masuk** (`resources/views/livewire/registry/incoming/index.blade.php`): Kolom *Perihal Surat*.
    3. **Arsip Memo Internal** (`resources/views/livewire/registry/internal/index.blade.php`): Kolom *Perihal Surat*.
    4. **Arsip Surat Keluar** (`resources/views/livewire/registry/outgoing/index.blade.php`): Kolom *Perihal*.
- **Mekanisme Kerja & Standar UX**:
  - **Tampilan Padat Standar**: Teks perihal secara *default* dibatasi maksimal 2 baris (`line-clamp-2`), mencegah pembengkakan tinggi baris tabel sehingga seluruh baris tabel tetap sejajar dan padat (*dense layout*).
  - **Ambang Batas Deteksi Otomatis**: Tombol **`Show More ▾`** hanya muncul jika teks perihal melebihi batas 75 karakter (`mb_strlen > 75`). Teks perihal yang singkat tetap tampil bersih tanpa tombol.
  - **Ekspansi Instan 0ms (Client-Side)**: Saat tombol diklik, Alpine.js (`x-data="{ expanded: false }"`) langsung mengganti kelas menjadi `whitespace-normal break-words` secara instan tanpa *server roundtrip* atau *refresh* layar.
  - **Penciutan Fleksibel**: Tombol otomatis bertransformasi menjadi **`Show Less ▴`** untuk mengembalikan teks ke format 2 baris.
- **Verifikasi**:
  - Cache view aplikasi telah dibersihkan (`view:clear`).
  - Suite pengujian otomatis `DocumentLoanTest` (5 *test cases*, 24 *assertions*) terkonfirmasi tetap lulus 100% (*PASS*).

### 32. Integrasi Penyimpanan NAS Enterprise (Konsep A: OS-Level Mount via SMB dengan Automatic Dynamic Fallback)
- **Arsitektur Penyimpanan Mandiri Disk `'nas'` (`config/filesystems.php`)**:
  - Didaftarkan disk penyimpanan baru `'nas'` berbasis driver `'local'`.
  - **Automatic Dynamic Fallback Engine**:
    - Nilai root disk dikonfigurasi cerdas: `env('NAS_STORAGE_PATH') ?: storage_path('app/public')`.
    - **Di PC Pengembang Lokal**: Saat variabel `NAS_STORAGE_PATH` kosong (tidak diset), sistem secara otomatis menyimpan berkas ke `storage/app/public` lokal. Pengembangan, pengujian form, dan pratinjau PDF di PC lokal 100% aman dan tidak terganggu sama sekali tanpa perlu server NAS fisik.
    - **Di Server Linux Produksi**: Sysadmin cukup mengaitkan share SMB dari Synology/QNAP NAS ke mount point OS (misal: `/mnt/nas_arsip`) lalu mengatur baris `.env`: `NAS_STORAGE_PATH=/mnt/nas_arsip/documents`. Seluruh berkas otomatis disimpan langsung ke harddisk server NAS tanpa mengubah satu baris kode pun.
- **Penyelarasan Seluruh Titik Simpan Berkas (*Storage Handlers*)**:
  - Seluruh controller dan komponen Livewire telah dialihkan menyimpan berkas ke disk `'nas'`:
    - `ScanQueue::saveFinalFile()`: `$this->finalFile->storeAs('documents', $fileName, 'nas');`
    - `Incoming\Create::handleFileAndVersion()`: `$this->file->storeAs('documents', $fileName, 'nas');`
    - `Outgoing\Create::handleFileAndVersion()`: `$this->file->storeAs('documents', $fileName, 'nas');`
    - `Internal\Create::handleFileAndVersion()`: `$this->file->storeAs('documents', $fileName, 'nas');`
    - `Incoming\Edit::submit()`: `$this->file->storeAs('documents', $fileName, 'nas');`
- **Rute Streaming PDF dengan Dual-Layer Resolution (`routes/web.php`)**:
  - Pada rute `/document/preview/{version}`, diterapkan pengecekan resolusi berkas 2 lapis:
    1. Memeriksa keberadaan fisik berkas pada disk `'nas'` (`Storage::disk('nas')->path($version->file_path)`).
    2. Jika tidak ditemukan pada mount NAS, sistem otomatis melakukan fallback memeriksa direktori publik lokal (`storage_path('app/public/' . $version->file_path)`).
    3. Jika kedua jalur tidak memuat file, baru sistem melempar status `404 Not Found`.
  - Menjamin dokumen warisan (*legacy files*) yang pernah diunggah sebelum migrasi tetap dapat dibuka dan dipratinjau dengan normal.
  - Memverifikasi otorisasi hak baca `view` berdasarkan sifat dokumen (`biasa`, `penting`, `rahasia`) sebelum merender berkas.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat suite pengujian fitur baru `Tests\Feature\NasStorageTest` yang menguji pendaftaran disk `nas`, mekanisme fallback lokal, dan streaming preview PDF dengan header resmi.
  - Seluruh pengujian otomatis aplikasi (`DocumentLoanTest`, `NasStorageTest`, `ExampleTest`) terkonfirmasi **lulus 100% (*PASS*, 9 tests, 35 assertions)**.

### 33. Penyimpanan Hierarki Loker Fisik di Server NAS (Dynamic Hierarchical Locker Directory Engine)
- **Struktur Direktori Bertingkat Menyerupai Loker Aplikasi**:
  - Berkas fisik yang tersimpan di server NAS tidak lagi menumpuk di satu folder datar (*flat*), melainkan secara otomatis dikelompokkan mengikuti kategori naskah dan pohon Loker Penyimpanan yang ditentukan:
    - Pola Jalur: `documents/{Kategori Naskah}/{Induk Loker}/{Sub Loker}/{Nomor Surat}_{YYYY-MM-DD}.pdf`
    - Contoh 1: `documents/Surat Keluar/Tahun 2026/Divisi Keuangan/001 M-IT 10 2026_2026-10-12.pdf`
    - Contoh 2: `documents/Surat Masuk/Tanpa Loker/002 SM X 2026_2026-10-12.pdf`
- **Helper Hierarki pada Model `App\Models\Folder` (`getDirectoryPath()`)**:
  - Mengambil rantai hierarki folder secara rekursif dari anak ke induk (`$current->parent`).
  - Menyaring dan membersihkan karakter terlarang sistem operasi (`/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|`) agar pembuatan direktori di Linux/Windows NAS aman tanpa error I/O.
- **Helper Jalur Penyimpanan pada Model `App\Models\Document` (`getStorageDirectoryPath()`)**:
  - Memetakan tipe naskah ke nama kategori berbahasa Indonesia resmi:
    - `'incoming'` ➔ `'Surat Masuk'`
    - `'outgoing'` ➔ `'Surat Keluar'`
    - `'internal'` ➔ `'Memo Internal'`
  - Menggabungkan kategori dengan hasil `getDirectoryPath()`. Jika dokumen belum memiliki loker, otomatis diarahkan ke folder subkategori `'Tanpa Loker'`.
- **Integrasi di Seluruh Titik Simpan Berkas (*Storage Handlers*)**:
  - `ScanQueue::saveFinalFile()`: Menetapkan loker pilihan Tapersip (`$document->folder_id = $this->uploadFolderId`) dan me-load relasi `folder.parent` **sebelum** mengeksekusi `storeAs()`, sehingga berkas langsung masuk ke subfolder loker bersangkutan di NAS.
  - `Incoming\Create::handleFileAndVersion()`: Menggunakan jalur direktori hierarki dokumen.
  - `Outgoing\Create::handleFileAndVersion()`: Menggunakan jalur direktori hierarki dokumen.
  - `Internal\Create::handleFileAndVersion()`: Menggunakan jalur direktori hierarki dokumen.
  - `Incoming\Edit::submit()`: Menggunakan jalur direktori hierarki dokumen saat submit revisi.
- **Verifikasi Pengujian Otomatis**:
  - Suite pengujian fitur `Tests\Feature\NasStorageTest::test_hierarchical_folder_path_generation_and_storage_structure` memvalidasi sanitasi nama loker, pembuatan jalur bertingkat, dan penyimpanan fisik di NAS disk.
  - Seluruh test suite (10 *test cases*, 40 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

