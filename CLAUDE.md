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

### 34. Panduan Deployment Server & Mounting Penyimpanan NAS (SMB/CIFS)
- **Lingkungan Produksi Server Lokal**:
  - IP Server Aplikasi: `192.168.30.155` (Ubuntu Server / Apache 2.4).
  - Web Server & Subfolder Alias: `/var/www/html/earsip` dengan alias URL `http://192.168.30.155/earsip`.
  - IP Perangkat NAS: `192.168.30.100` (Protokol SMB/CIFS).
  - Share Folder NAS: `//192.168.30.100/Tapersip`.
  - Mount Point Lokal Server: `/mnt/tapersip_nas`.
- **Langkah-Langkah Mounting NAS SMB/CIFS ke Server Ubuntu**:
  1. **Install Paket Pendukung CIFS**:
     ```bash
     sudo apt update && sudo apt install cifs-utils -y
     ```
  2. **Buat Direktori Titik Mount**:
     ```bash
     sudo mkdir -p /mnt/tapersip_nas
     ```
  3. **Buat File Kredensial NAS (Aman & Terenkapsulasi)**:
     - Buat file kredensial di `/etc/nas-credentials`:
       ```ini
       username=NAMA_USER_NAS
       password=PASSWORD_NAS
       ```
     - Kunci hak akses file agar hanya `root` yang dapat membaca:
       ```bash
       sudo chmod 600 /etc/nas-credentials
       ```
  4. **Mounting Manual & Pemetaan Hak Akses Web Server (`www-data`)**:
     - Hubungkan share folder NAS dengan kepemilikan user Apache agar dapat membaca dan menulis file:
       ```bash
       sudo mount -t cifs //192.168.30.100/Tapersip /mnt/tapersip_nas -o credentials=/etc/nas-credentials,uid=www-data,gid=www-data,file_mode=0775,dir_mode=0775
       ```
     - Uji coba izin tulis:
       ```bash
       sudo -u www-data touch /mnt/tapersip_nas/test_koneksi.txt && sudo -u www-data rm /mnt/tapersip_nas/test_koneksi.txt
       ```
  5. **Konfigurasi Auto-Mount saat Booting Server (`/etc/fstab`)**:
     - Tambahkan baris konfigurasi di baris terbawah file `/etc/fstab`:
       ```text
       //192.168.30.100/Tapersip /mnt/tapersip_nas cifs credentials=/etc/nas-credentials,uid=www-data,gid=www-data,file_mode=0775,dir_mode=0775,nofail,_netdev 0 0
       ```
  6. **Integrasi Konfigurasi Laravel (`.env`)**:
     - Masuk ke direktori aplikasi `/var/www/html/earsip` dan tambahkan variabel lingkungan pada `.env`:
       ```ini
       NAS_STORAGE_PATH=/mnt/tapersip_nas
       ```
     - Bersihkan cache konfigurasi Laravel:
       ```bash
       php artisan config:clear
       ```
- **Catatan Penting Deploy Subfolder Apache (`/earsip`)**:
  - Konfigurasi VirtualHost `/etc/apache2/sites-available/000-default.conf` wajib menggunakan direktif `Alias /earsip /var/www/html/earsip/public`.
  - File `.htaccess` pada `public/.htaccess` wajib memuat `RewriteBase /earsip/` tepat di bawah `RewriteEngine On` untuk mencegah *internal rewrite loop 500*.
  - Variabel `APP_URL` dan `ASSET_URL` pada `.env` harus bernilai `http://192.168.30.155/earsip`.

### 35. Penyetelan Nomor Terakhir / Sequence Awal Manual & Fleksibilitas Input Nomor Fisik (Smart Transition Sequence Engine)
- **Skema Database & Model**:
  - Migrasi `2026_10_13_071503_add_sequence_fields_to_document_types_and_documents_tables.php`:
    - Tabel `document_types`: Menambahkan `starting_sequence` (unsignedInteger, default 0) dan `starting_sequence_year` (smallInteger, nullable).
    - Tabel `documents`: Menambahkan `sequence_number` (unsignedInteger, nullable, indexed).
  - Model `DocumentType`: Properti `$fillable` ditambahkan `'starting_sequence'` dan `'starting_sequence_year'`.
  - Model `Document`: Properti `$fillable` ditambahkan `'sequence_number'`.
- **Pengaturan Master Data Sequence Awal (`ArchiveClassificationIndex`)**:
  - Pada modal Tambah/Edit Jenis Dokumen (`slide-doctype`), ditambahkan field input:
    - **Nomor Terakhir Buku Manual** (`typeStartingSequence`): Untuk menampung nomor buku fisik terakhir sebelum aplikasi diluncurkan (misal: 85).
    - **Tahun Berlaku Urutan** (`typeStartingYear`): Untuk membatasi berlakunya sequence awal hanya pada tahun berjalan (default: tahun berjalan). Reset otomatis ke 001 saat berganti tahun.
  - Tabel master data menampilkan informasi urutan manual aktif di bawah format penomoran.
- **Rumus Penomoran Cerdas Inkremental (`max(DB, StartingSequence) + 1`)**:
  - Pada `app/Livewire/Registry/Outgoing/Create.php` dan `app/Livewire/Registry/Internal/Create.php`:
    - Mengambil nilai tertinggi dari `sequence_number` dokumen tahun berjalan (`$maxInDb`).
    - Mengambil nilai `starting_sequence` dari master data jika tahunnya cocok (`$startingSequence`).
    - Menghitung urutan naskah berikutnya: `max($maxInDb, $startingSequence) + 1`.
    - Menjamin nomor urut tidak pernah mundur, melompati urutan, atau bertabrakan saat masa transisi go-live.
- **Fleksibilitas Input Nomor Fisik Khusus Tapersip (Dual-Mode Input)**:
  - Pada formulir input Surat Keluar dan Memo Internal:
    - **Staf Biasa (Tanpa Izin `archive`)**: Field input nomor surat disembunyikan sepenuhnya. Staf hanya dapat menerbitkan nomor resmi urut otomatis melalui tombol *"Ambil Nomor Surat"*.
    - **Petugas Tapersip (Memiliki Izin `archive` "Arsip & Upload Langsung")**: Field input nomor fisik (`Nomor Surat Fisik`) dimunculkan di Section 1 (Informasi Surat).
      - Jika diisi nomor manual (dokumen arsip lama sebelum sistem ada), sistem menyimpan nomor asli tersebut apa adanya dan mengekstrak nilai angka urutnya ke `sequence_number`.
      - Jika dikosongkan, sistem secara otomatis menerbitkan nomor berikutnya yang urut.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat suite pengujian fitur `Tests\Feature\SequenceNumberingTest`:
    - Menguji penyetelan sequence awal di master data jenis dokumen.
    - Menguji booking nomor otomatis berangkat dari angka awal (misal: 85 ➔ 086 ➔ 087).
    - Menguji penyimpanan naskah arsip fisik lama dengan nomor manual oleh Tapersip.
  - Seluruh pengujian otomatis proyek (13 *test cases*, 51 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 36. Perombakan Template Cetak Tiket Peminjaman Fisik Sesuai Standar Resmi & Format Kertas A4 (Printable A4 Physical Archive Loan Ticket)
- **Desain Kop Tiket Resmi & Penggantian Logo SVG (`document-loan-index.blade.php`)**:
  - Logo kotak teks `"EA"` digantikan dengan logo resmi instansi berformat SVG: `{{ asset('storage/assets/logo_cms_hd_png.svg') }}` dalam container seimbang `w-12 h-12 object-contain`.
  - Garis pemisah vertikal memisahkan logo dengan judul kop dinas.
  - Judul kop tersusun dalam 2 baris tegas huruf kapital:
    - **`TIKET BUKTI PEMINJAMAN`**
    - **`ARSIP FISIK`**
    - Subjudul: `TATA PERSURATAN & KEARSIPAN RESMI (TAPERSIP)`.
  - Sisi kanan memuat badge nomor tiket (`TIKET-YYYYMM-XXXX`) dengan bingkai tegas dan waktu pengajuan (`d/m/Y H:i`).
- **Highlight Lokasi Loker Fisik (`📁 LOKASI LEMARI / MAP ARSIP FISIK`)**:
  - Kotak sorot dengan border putus-putus biru (`border-dashed border-blue-400 bg-blue-50/30 rounded-xl`).
  - Menampilkan ikon folder biru dan teks hierarki loker lengkap (`$document->folder->getDirectoryPath()`) agar petugas Tapersip dapat langsung mengambil berkas fisik tanpa mencari manual.
- **Tabel Dua Kolom Sejajar Titik Dua (Identitas Pemohon & Identitas Naskah)**:
  - Kolom Kiri (**`IDENTITAS PEMOHON:`**): Nama pemohon (bold), Jabatan, dan Unit Kerja.
  - Kolom Kanan (**`IDENTITAS NASKAH:`**): Nomor Surat Resmi (bold monospace), Tanggal Surat, dan Sifat Dokumen (BIASA / PENTING / RAHASIA).
- **Bagian Konten Naskah & Transaksi Sirkulasi**:
  - **`PERIHAL DOKUMEN:`**: Memuat judul perihal berkas secara lengkap tanpa terpotong (*no truncation*).
  - **`KEPERLUAN PEMINJAMAN:`**: Menampilkan rincian tujuan peminjaman fisik.
  - **Rentang Waktu**: Tanggal mulai pinjam dan batas kembali dengan penekanan tanggal jatuh tempo berwarna merah tegas.
- **Dua Kolom Tanda Tangan Basah Simetris**:
  - Sisi Kiri: *Peminjam Berkas* (Ruang tanda tangan, garis penandatangan, nama pemohon, dan unit kerja).
  - Sisi Kanan: *Petugas Layanan Tapersip* (Ruang tanda tangan, garis penandatangan, nama petugas/placeholder, dan Unit Kearsipan).
  - Catatan Footer Miring: `* Harap simpan tiket ini dan serahkan kembali bersama berkas fisik saat pengembalian ke Tapersip.`
- **Kalibrasi Cetak Standar Kertas A4 Portrait (`@media print`)**:
  - Dikonfigurasi `@page { size: A4 portrait; margin: 10mm 15mm; }`.
  - Kontainer `#printable-ticket` dikunci pada lebar `max-width: 180mm` dengan `page-break-inside: avoid` dan `page-break-after: avoid`.
  - Memastikan tiket saat dicetak melalui browser (*Print to PDF* atau printer fisik) **pas tepat 1 lembar A4 portrait penuh**, tidak terpotong, dan tidak menyisakan halaman kedua kosong.
- **Verifikasi Pengujian**:
  - Seluruh pengujian otomatis proyek (`DocumentLoanTest`, `NasStorageTest`, `SequenceNumberingTest`, `ExampleTest`) terkonfirmasi lulus 100% (*13 tests, 51 assertions*).

### 37. Penanganan Dual Kondisi Penomoran Surat Keluar (Direktur vs Pimpinan Unit Kerja, Format 2 Digit, & Nomor Urut Terpisah)
- **Skema Database & Model**:
  - Migrasi `2026_10_13_132642_add_signer_type_and_dir_sequence_tables.php`:
    - Tabel `documents`: Menambahkan kolom `signer_type` (`enum('pimpinan_unit', 'direktur')`, default `'pimpinan_unit'`, berindeks).
    - Tabel `document_types`: Menambahkan kolom `starting_sequence_dir` (unsignedInteger, default 0).
  - Model `Document`: Menambahkan `'signer_type'` ke dalam `$fillable`.
  - Model `DocumentType`: Menambahkan `'starting_sequence_dir'` ke dalam `$fillable`.
- **Formulir Registrasi Surat Keluar (`outgoing/create.blade.php`)**:
  - Pada Section 1 (Informasi Surat), ditambahkan komponen minimalis **Pilihan Pejabat Penandatangan Surat** (`wire:model.live="signer_type"`):
    1. **Pimpinan Unit Kerja** (`pimpinan_unit`): Format `01/[UNIT]-[KLASI]` (contoh: `01/OPS-HM.00/10/2026`).
    2. **Direktur** (`direktur`): Format `01/DIR-[UNIT]-[KLASI]` (contoh: `01/DIR-OPS-HM.00/10/2026`).
  - **Desain Minimalis & Bersih (Text-Only Selector)**:
    - Menghilangkan ikon-ikon dekoratif yang berlebihan, subteks panjang, dan contoh format yang membingungkan.
    - Menggunakan dua tombol pilihan teks bersih (*Pimpinan Unit Kerja* & *Direktur*) berbingkai rapi (`rounded-lg border px-4 py-2.5 text-xs text-center`) dengan highlight aktif elegan (`border-[var(--color-primary)] bg-emerald-50/40 text-[var(--color-primary)] font-bold ring-1`).
    - Menjaga ketinggian dan kerapatan form tetap simetris dan serasi dengan kolom input lainnya.
- **Logika Backend Nomor Urut Terpisah & 2 Digit (`Outgoing\Create.php`)**:
  - **Opsi B (Nomor Urut Terpisah)**: Kueri nomor urut dipartisi secara independen per pejabat penandatangan:
    `Document::where('document_type_id', $id)->where('type', 'outgoing')->where('signer_type', $this->signer_type)->whereYear('document_date', $year)->max('sequence_number')`
    - Surat Direktur dan Surat Pimpinan Unit Kerja masing-masing memiliki buku urut mandiri (tidak saling mendahului atau bertabrakan).
  - **Nomor Urut 2 Digit**: Format nomor urut menggunakan padding 2 digit: `str_pad($nextSequence, 2, '0', STR_PAD_LEFT)` (`01`, `02`, dst.).
  - **Formula Penggantian Cerdas Prefix `DIR-`**:
    - Jika penandatangan adalah **Direktur**: Nilai placeholder `{DIR}` otomatis diisi `DIR-`, atau jika format master data belum memiliki `{DIR}`, sistem otomatis menyematkan prefix `DIR-` pada kode unit (`DIR-{$unitCode}`).
    - Jika penandatangan adalah **Pimpinan Unit Kerja**: Placeholder `{DIR}` dikosongkan dan kode unit tetap murni (`{$unitCode}`).
- **Pembaruan Master Data Jenis Dokumen (`ArchiveClassificationIndex`)**:
  - Modal form jenis dokumen menyediakan 2 kolom sequence awal manual mandiri:
    - *No. Manual Unit Kerja* (`typeStartingSequence`).
    - *No. Manual Direktur* (`typeStartingSequenceDir`).
    - *Tahun Berlaku* (`typeStartingYear`).
  - Tabel master data menampilkan rincian kedua urutan manual aktif.
- **Verifikasi Pengujian Otomatis**:
  - Test case `SequenceNumberingTest::test_outgoing_dual_condition_numbering_for_director_and_unit_head` memvalidasi:
    - Penerbitan nomor Direktur: `01/DIR-OPS-HM.00/10/2026`.
    - Penerbitan nomor Pimpinan Unit Kerja: `01/OPS-HM.00/10/2026` (nomor urut terpisah dari 01 mandiri).
    - Penerbitan nomor Direktur kedua: `02/DIR-OPS-HM.00/10/2026`.
  - Seluruh pengujian otomatis proyek (14 *test cases*, 60 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 38. Default & Penguncian Read-Only Jenis Dokumen pada Form Registrasi Surat Keluar
- **Formulir Registrasi Surat Keluar (`outgoing/create.blade.php`)**:
  - Kolom **Jenis Dokumen** pada Section 2 (*Klasifikasi & Atribut*) tetap mempertahankan elemen form kontrol `<select wire:model="document_type_id">` asli.
  - Elemen dropdown disetel *disabled/read-only* dengan styling `bg-gray-100/80 text-gray-700 cursor-not-allowed select-none` untuk mencegah manipulasi pilihan.
  - Opsi kosong `-- Pilih Jenis Dokumen --` ditiadakan sehingga opsi langsung terpilih (*default*) ke jenis dokumen `"Surat Keluar"` yang terpasang dari backend.
- **Backend Component (`Outgoing\Create.php`)**:
  - Pada metode `mount()`, sistem secara otomatis mendeteksi dan mengaitkan ID master data `"Surat Keluar"` ke `$document_type_id`.
  - Jika master data belum tersedia, sistem secara otomatis melakukan inisialisasi awal (*firstOrCreate*) dengan format standar penomoran keluar `{NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}`.
- **Verifikasi Pengujian Otomatis**:
  - Ditambahkan pengujian otomatis `SequenceNumberingTest::test_outgoing_create_defaults_to_surat_keluar_document_type` yang memverifikasi bahwa inisialisasi form langsung mengunci ID jenis dokumen Surat Keluar.
  - Seluruh pengujian otomatis proyek (15 *test cases*, 62 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 39. Diferensiasi Unit Kerja (Divisi vs Departemen) & Filter Dropdown Korespondensi
- **Skema Database & Model**:
  - Migrasi `2026_10_15_000001_add_type_to_units_table.php`: Menambahkan kolom `type` (`enum('division', 'department')`, default `'division'`) setelah kolom `name` pada tabel `units`.
  - Model `Unit`:
    - Menambahkan `'type'` ke dalam array `$fillable`.
    - Menambahkan local query scope: `scopeDivisions()` (`where('type', 'division')`) dan `scopeDepartments()` (`where('type', 'department')`).
    - Menjaga relasi hierarki self-referencing `parent_id` (Departemen menginduk ke Divisi).
  - Seeder `UnitSeeder`:
    - Mengklasifikasikan unit utama (IT, HRD, FIN, LGL, OPS) sebagai `division`.
    - Menambahkan data awal sub-unit departemen: `IT-DEV`, `IT-INF`, `HRD-REC`, `FIN-PAY`, `OPS-LOG` dengan `type = 'department'` dan mengaitkan `parent_id` ke divisi induk masing-masing.
- **Master Data Organisasi (`organization-index.blade.php` & `OrganizationIndex.php`)**:
  - **Segmented Filter Pills**: Menambahkan tombol filter jenis unit di toolbar tab Unit Kerja: `[ Semua Unit ]`, `[ Divisi ]`, `[ Departemen ]`.
  - **Pencarian Real-Time**: Input pencarian kode dan nama unit dihubungkan secara reaktif (`wire:model.live.debounce.300ms="searchUnit"`).
  - **Tabel Unit Kerja**: Menambahkan kolom badge **Tipe** (`Divisi` dengan aksen indigo, `Departemen` dengan aksen teal) dan kolom **Induk Divisi** yang menampilkan nama dan kode divisi induk.
  - **Modal Tambah/Edit Unit**:
    - Komponen radio selector rapi untuk memilih tipe: `Divisi (Induk)` atau `Departemen (Sub-Unit)`.
    - Pilihan **Divisi Induk** ditampilkan secara kondisional dan wajib dipilih jika tipe adalah `Departemen`. Jika `Divisi`, kolom induk otomatis disembunyikan dan `parent_id` disimpan sebagai `null`.
- **Filtering Dropdown Korespondensi**:
  - **Registrasi Surat Keluar (`Outgoing\Create.php` & `outgoing/create.blade.php`)**:
    - Dropdown **Unit Pengirim** dikunci hanya mengambil unit dengan tipe `division` (`Unit::divisions()->where('status', 'active')->orderBy('name')->get()`).
    - Label diperjelas menjadi *"Unit Pengirim (Divisi)"*.
  - **Registrasi Memo Internal (`Internal\Create.php` & `internal/create.blade.php`)**:
    - Dropdown **Unit Pengirim** dan **Unit Tujuan** dikunci hanya mengambil unit dengan tipe `department` (`Unit::departments()->with('parent')->where('status', 'active')->orderBy('name')->get()`).
    - Label diperjelas menjadi *"Unit Pengirim (Departemen)"* dan *"Unit Tujuan (Departemen)"*.
    - Opsi dropdown menyertakan konteks divisi induk: `[Nama Departemen] ([Kode]) - [Nama Divisi Induk]`.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat test suite baru `tests/Feature/OrganizationUnitTest.php` (4 test cases, 25 assertions):
    - `test_admin_can_filter_units_by_type_division_and_department`
    - `test_admin_can_create_division_and_department_with_parent`
    - `test_outgoing_create_only_contains_division_units`
    - `test_internal_create_only_contains_department_units`
  - Seluruh rangkaian pengujian proyek (19 *test cases*, 87 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 40. Pembaruan Nomenklatur & URL: Penggantian "Memo Internal" Menjadi "Surat Khusus"
- **Routing & URL Sejajar (`routes/web.php`)**:
  - Halaman arsip naskah khusus dialihkan ke URL standar bahasa Inggris: `/registry/special` (`name: registry.special.index`).
  - Halaman pendaftaran/booking dialihkan ke URL: `/registry/special/create` (`name: registry.special.create`).
  - Rute lama `/registry/internal` dan `/registry/internal/create` tetap disediakan sebagai rute pengarah balik (*backward-compatible redirect*) demi mencegah tautan mati (*broken links*).
- **Navigasi Sidebar (`components/layouts/app.blade.php`)**:
  - Menu input surat: `"Memo Internal"` diperbarui menjadi **`"Surat Khusus"`** (`route('registry.special.create')`).
  - Menu kearsipan explorer: `"Arsip Memo Internal"` diperbarui menjadi **`"Arsip Surat Khusus"`** (`route('registry.special.index')`).
- **Teks Formulir & Petunjuk Alur Kerja (`resources/views/livewire/registry/internal/create.blade.php` & `Create.php`)**:
  - Judul form registrasi diubah menjadi **"Registrasi Surat Khusus"**.
  - Teks deskripsi dan panduan staf pada kartu alur kanan diubah menjadi petunjuk penerbitan nomor Surat Khusus resmi.
  - Flash notification saat simpan/arsip diubah menjadi *"Dokumen Surat Khusus berhasil disimpan..."* dan mengarahkan ke `registry.special.index`.
- **Explorer Loker & Page Wrappers (`internal/index.blade.php` & pages)**:
  - Judul modal loker disesuaikan menjadi **"Tambah Loker Surat Khusus"**.
  - Header halaman wrapper diubah menjadi **"Surat Khusus"** dengan deskripsi *"Kelola dan pantau seluruh dokumen surat khusus"*.
- **Penyimpanan NAS & Model Document (`Document.php`)**:
  - Metode `Document::getStorageDirectoryPath()` untuk dokumen tipe `'internal'` disesuaikan menjadi folder fisik **`documents/Surat Khusus/...`**.
- **Dashboard, Antrean Scan, Peminjaman Surat, & Role Matrix**:
  - Kartu statistik 3: **"Total Surat Khusus"**.
  - Badge jenis naskah pada tabel dokumen di Dashboard, Antrean Scan Tapersip, dan Sirkulasi Peminjaman Berkas diubah menjadi **`"Surat Khusus"`**.
  - Role editor: Label hak akses diperjelas menjadi *"Input Surat Khusus"*, *"Arsip Surat Khusus"*, dan *"Total Surat Khusus"*.
- **Pengecualian Khusus Sesuai Arahan**:
  - Profil jenis dokumen pada master data `document_types` dengan nama **"Memo Internal"** (format `{NO}/MI/{UNIT}/{YYYY}`) tetap dipertahankan murni tanpa perubahan.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat test suite baru `tests/Feature/SpecialLetterTest.php` yang memverifikasi akses route baru `/registry/special`, pengalihan route lama, dan penamaan folder direktori penyimpanan Surat Khusus.
  - Seluruh pengujian proyek (21 *test cases*, 96 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 41. Kode Jenis Dokumen & Variabel Dinamis {KOSUR} pada Format Penomoran
- **Skema Database & Model (`DocumentType`)**:
  - Migrasi `2026_10_15_000002_add_code_to_document_types_table.php`: Menambahkan kolom `code` (`string(20)->nullable()`) setelah kolom `name` pada tabel `document_types`.
  - Model `DocumentType`: Menambahkan `'code'` ke dalam `$fillable`.
  - Seeder `DocumentTypeSeeder`: Mengisi kode baku dan format untuk jenis dokumen:
    - *Surat Keluar* ➔ `code: SK`
    - *Surat Edaran* ➔ `code: SE`
    - *PKS* ➔ `code: PKS`
    - *Memo Internal* ➔ `code: MI`
    - *Surat Keputusan* ➔ `code: SKP`
    - *Surat Tugas* ➔ `code: ST`
- **Master Data Jenis Dokumen (`ArchiveClassificationIndex.php` & Blade)**:
  - **Tabel Jenis Dokumen**: Ditambahkan kolom pertama **Kode (KOSUR)** dengan font monospace badge rapi (`font-mono text-xs bg-slate-100`).
  - **Modal Tambah/Edit Jenis Dokumen**:
    - Input grid simetris: **Kode (KOSUR) \*** (`wire:model.live="typeCode"`) dan **Nama Jenis Dokumen \*** (`wire:model="typeName"`).
    - Validasi: `required|string|max:20|unique:document_types,code,ID`.
    - Helper format penomoran: Mengikutsertakan variabel baku `{KOSUR}`.
    - Live Preview Penomoran: Menggantikan `{KOSUR}` secara *real-time* sesuai apa yang diketik admin di input kode.
- **Generator Nomor Surat (`Outgoing\Create.php` & `Internal\Create.php`)**:
  - Variabel `{KOSUR}` dan `{kosur}` otomatis diparsing dan digantikan dengan kode jenis naskah (`$docTypeRecord?->code`).
- **Verifikasi Pengujian Otomatis**:
  - Pengujian `SequenceNumberingTest::test_admin_can_set_document_type_code_in_master_data` dan `SequenceNumberingTest::test_numbering_format_replaces_kosur_placeholder`.
  - Seluruh rangkaian test suite (23 *test cases*, 100 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 42. Resolusi Bug UniqueConstraintViolationException pada Master Data Jenis Dokumen
- **Akar Masalah**:
  - Pada metode `saveDocType()` sebelumnya, field `typeName` belum memiliki aturan validasi keunikan (`unique:document_types,name,ID`), sementara di tingkat database MySQL terdapat indeks unik `document_types_name_unique`.
  - Saat pengguna mengedit jenis dokumen lain dan memasukkan nama yang sudah ada (misalnya: mengubah "PKS" menjadi "Surat Keputusan"), validator Livewire meloloskannya sehingga Laravel langsung meluncurkan query `UPDATE` mentah ke MySQL, yang mengakibatkan aplikasi mengalami crash fatal `1062 Duplicate entry`.
  - Selain itu, penggunaan *SoftDeletes* pada tabel `document_types` berpotensi memicu tabrakan constraint unik MySQL jika record yang dihapus masih tersimpan dengan nama yang sama.
- **Perbaikan Backend & Validasi (`ArchiveClassificationIndex.php`)**:
  - Menambahkan aturan validasi eksplisit dengan pengecualian ID saat ini dan pengecualian baris soft-deleted:
    `'typeName' => ['required', 'string', 'max:255', Rule::unique('document_types', 'name')->ignore($this->typeId)->whereNull('deleted_at')]`
    `'typeCode' => ['required', 'string', 'max:20', Rule::unique('document_types', 'code')->ignore($this->typeId)->whereNull('deleted_at')]`
  - Menyediakan pesan validasi ramah pengguna dalam bahasa Indonesia:
    `"Nama Jenis Dokumen sudah digunakan oleh jenis naskah lain."`
    `"Kode Jenis Dokumen (KOSUR) sudah digunakan oleh jenis naskah lain."`
  - Menyematkan blok defensif `try...catch (UniqueConstraintViolationException $e)` yang menangkap kemungkinan race condition di tingkat database dan mengubahnya menjadi pesan error formulir tanpa pernah melempar error 500 ke pengguna.
- **Skema Database (`2026_10_15_000003_drop_unique_name_from_document_types_table.php`)**:
  - Melepas constraint kaku `document_types_name_unique` dari database agar kompatibel penuh dengan arsitektur *SoftDeletes*, dan menyerahkan penegakan keunikan aktif secara presisi kepada validasi Laravel.
- **Verifikasi Pengujian Otomatis**:
  - Ditambahkan pengujian otomatis `SequenceNumberingTest::test_duplicate_document_type_name_or_code_returns_validation_error_gracefully`.
  - Seluruh rangkaian unit & feature test suite (24 *test cases*, 106 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 43. Dukungan Variabel Dinamis {DIV} untuk Resolusi Divisi Akun pada Format Penomoran
- **Model Hierarki Organisasi (`Unit` & `User`)**:
  - Model `Unit`:
    - Menambahkan `getDivision(): ?Unit` untuk melacak divisi induk (atau mengembalikan unit itu sendiri jika sudah bertipe `'division'`).
    - Menambahkan `getDivisionCode(): string` untuk mengekstrak singkatan kode divisi.
  - Model `User`:
    - Menambahkan `getDivision(): ?Unit` dan `getDivisionCode(): string` yang secara otomatis mengidentifikasi divisi tempat akun staf bernaung.
- **Generator Nomor Surat (`Outgoing\Create.php` & `Internal\Create.php`)**:
  - Variabel `{DIV}` dan `{div}` secara otomatis digantikan dengan kode divisi akun pembuat surat (`Auth::user()?->getDivisionCode()`).
  - Mekanisme *fallback* cerdas: jika akun login belum memiliki unit (misal Superadmin), sistem mengambil kode divisi dari unit yang dipilih pada formulir, atau fallback ke `'DIV'` guna mencegah nomor rusak atau slash ganda (`//`).
- **Antarmuka Pengaturan Kearsipan (`archive-classification-index.blade.php`)**:
  - Menambahkan badge panduan variabel `{DIV}` (`bg-indigo-50 text-indigo-700`) beserta catatan informasi bahwa `{DIV}` dibaca otomatis dari akun login.
  - Live Preview penomoran langsung merefleksikan kode divisi akun pengguna secara *real-time*.
- **Verifikasi Pengujian Otomatis**:
  - Ditambahkan pengujian `SequenceNumberingTest::test_numbering_format_replaces_div_placeholder_from_user_division` yang memvalidasi bahwa staf pada suatu departemen otomatis menghasilkan nomor dengan kode divisi induknya.
  - Ditambahkan kelengkapan username pada `UserFactory`.
  - Seluruh rangkaian pengujian (25 *test cases*, 108 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 44. Pemfungsian Log Aktivitas Sistem Dashboard & Pembatasan Hak Akses Personal/Tapersip
- **Model `AuditLog`**:
  - Menambahkan method static `AuditLog::record(string $action, ?int $documentId = null, array $metadata = [], ?int $userId = null): self` sebagai helper pencatatan log terpusat dan terstandarisasi.
- **Pencatatan Otomatis pada Alur Transaksional Aplikasi**:
  - **Surat Keluar (`Outgoing\Create.php`)**: Mencatat aksi booking nomor dan registrasi/arsip langsung.
  - **Surat Khusus (`Internal\Create.php`)**: Mencatat aksi booking nomor dan registrasi/arsip langsung.
  - **Surat Masuk (`Incoming\Create.php`)**: Mencatat aksi pendaftaran surat masuk.
  - **Antrean Scan Tapersip (`ScanQueue.php`)**: Mencatat aksi pengunggahan berkas scan PDF final.
  - **Peminjaman Berkas Fisik (`DocumentLoanIndex.php`)**: Mencatat pengajuan pinjam, persetujuan fisik, pengembalian berkas, dan penolakan tiket peminjaman.
- **Pembedaan Hak Akses Audit Trail (`User.php` & `Dashboard.php`)**:
  - Model `User`: Menambahkan `isTapersip(): bool` dan `canViewAllActivities(): bool` untuk mendeteksi apakah akun berhak memantau seluruh aktivitas sistem.
  - `Dashboard.php`:
    - Jika user adalah **Superadmin atau Petugas Tapersip**: Kueri menampilkan rekam jejak pengawasan seluruh pengguna sistem (`canViewAllActivities = true`).
    - Jika user adalah **Staf Biasa**: Kueri dibatasi secara otomatis hanya untuk aktivitas yang dilakukan oleh akun staf tersebut (`where('user_id', Auth::id())`).
- **Redesain Antarmuka Timeline Dashboard (`dashboard.blade.php`)**:
  - Menghilangkan layout *zigzag* 2-sisi yang sebelumnya pecah pada container sempit.
  - Mengimplementasikan **Clean Monochromatic Flat Activity List**:
    - Menghilangkan konsep kotak-dalam-kotak (*nested boxes*) dan garis timeline ganda.
    - Menghilangkan warna pembeda (tanpa dot/badge warna-warni) sehingga tampilan sangat tenang, netral (*slate*), dan bersih (*enterprise minimalist*).
    - Pola hierarki 2 baris ringkas: Aksi & waktu relatif di baris atas, nomor surat resmi + nama staf + subjek ringkas di baris bawah.
    - Dipisahkan dengan garis rambut halus (`divide-y divide-slate-100`) dan efek hover lembut.
    - Header widget dilengkapi badge netral: `"Seluruh Sistem"` vs `"Aktivitas Anda"`.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat test suite `tests/Feature/DashboardActivityLogTest.php` (4 test cases, 15 assertions):
    - `test_activity_is_recorded_when_outgoing_letter_is_booked`
    - `test_staff_only_sees_their_own_activities_on_dashboard`
    - `test_superadmin_sees_all_activities_on_dashboard`
    - `test_tapersip_sees_all_activities_on_dashboard`
  - Seluruh rangkaian test suite (29 *test cases*, 123 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 45. Ekspor Master Data Database Menjadi Seeder Resmi (Units, Document Types, Roles, & Classifications)
- **Tujuan Arsitektur**:
  - Memastikan seluruh konfigurasi master data yang sudah disetel di database tersimpan secara permanen ke dalam file seeder Laravel, sehingga saat migrasi/deployment ke server baru (`php artisan migrate --seed`), seluruh master data langsung tersedia tanpa perlu entri manual ulang.
- **Pembaruan 4 Berkas Seeder**:
  1. **`UnitSeeder.php`**:
     - Memuat **13 unit kerja riil**: 4 Divisi (`TEK`, `OPS`, `SDM`, `KEU`) dan 9 Departemen (`MGTTI`, `PP`, `HR`, `FIN`, `AKT`, `HMS`, `HK`, `UM`, `SEKR`).
     - Relasi `parent_id` dihubungkan secara dinamis melalui pencarian `parent_code` pada saat seeding.
  2. **`DocumentTypeSeeder.php`**:
     - Memuat **15 jenis dokumen lengkap** beserta kode singkatan (KOSUR), sifat default, dan formula penomoran dinamis:
       - Surat Keluar (`SK`), Surat Edaran (`SE`), Surat Perintah (`SPRIN`), Memo Internal (`M`), Surat Keputusan (`KPTS`), Surat Tugas (`STG`), Surat Pengumuman (`PENG`), Surat Perjanjian Kerja (`SPJK`), SuratPerjalananDinas (`SPD`), Berita Acara (`BA`), Surat Kuasa (`SKS`), Surat Keterangan (`SKTR`), Notulen (`NTL`), Surat Pernyataan (`SPN`), Surat Perintah Kerja (`SPK`).
  3. **`RoleSeeder.php`**:
     - Memuat **6 peran sistem**: `Superadmin`, `Admin`, `Manager`, `User`, `AdminTapersip`, `Staf`.
     - Menyimpan konfigurasi array `module_visibility` per role.
     - Menyinkronkan seluruh matriks izin (`role_permission`) untuk sifat naskah *biasa*, *penting*, dan *rahasia*.
  4. **`ClassificationSeeder.php`**:
     - Memuat seluruh **105 data klasifikasi arsip** beserta masa aktif JRA, inaktif, dan nasib akhir berkas (*permanen / musnah / dinilai kembali*).
     - Relasi hierarki pohon klasifikasi `parent_id` dihubungkan secara dinamis via `parent_code`.
- **Penyesuaian `DatabaseSeeder.php`**:
  - Menghubungkan akun bawaan Superadmin secara dinamis ke unit kerja pertama (`TEK`) tanpa hardcode ID.
- **Verifikasi Database & Pengujian**:
  - Perintah `php artisan db:seed` berhasil mengeksekusi seluruh seeder tanpa kendala (105 klasifikasi, 13 unit, 6 role, 15 jenis dokumen).
  - Seluruh rangkaian test suite (29 *test cases*, 126 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 46. Penyatuan Hak Akses Tab "Katalog & Cari Surat" dan "Pinjaman Saya" pada Manajemen Kepatuhan
- **Relokasi Hak Akses Role (`role-editor.blade.php`)**:
  - Menghapus checklist `document_borrowing` dari Blok 1 (*Alur Kerja Utama*).
  - Memindahkan checklist `document_borrowing` ke Blok 2 (**Manajemen Kepatuhan**) dengan label **`Katalog & Pinjaman Saya`** bersanding dengan `loan_circulation` (*Sirkulasi & Persetujuan Tapersip*).
- **Multi-Modul Route Access (`CheckModuleAccess.php` & `routes/web.php`)**:
  - Memperbarui middleware `CheckModuleAccess` agar mendukung parameter variadik (`string ...$modules`), mengecek apakah akun memiliki salah satu dari izin yang ditentukan.
  - Route `/borrowings` diamankan dengan `module:document_borrowing,loan_circulation`.
  - Sidebar kearsipan menampilkan tautan *"Cari & Pinjam Surat"* jika user memiliki `document_borrowing` atau `loan_circulation`.
- **Logika Kontrol Tab Adaptif (`DocumentLoanIndex.php` & Blade)**:
  - Menambahkan computed property `canBorrow` (`Auth::user()->isSuperAdmin() || Auth::user()->hasModuleAccess('document_borrowing')`).
  - Menentukan tab aktif awal secara adaptif di `mount()`:
    - Jika user memiliki `canBorrow`, default ke tab `'catalog'`.
    - Jika user hanya memiliki `canManageCirculation`, default otomatis dialihkan ke tab `'circulation'`.
  - Pada `setTab($tab)`: memproteksi tab sirkulasi maupun katalog/pinjaman agar user tidak bisa memaksakan URL/state tab di luar izin perannya.
  - Memproteksi aksi pengajuan pinjam (`openLoanModal` dan `submitLoanRequest`) dengan pengecekan `canBorrow`.
  - Pada tampilan Blade `document-loan-index.blade.php`:
    - Tab 1 (*Katalog & Cari Surat*) dan Tab 2 (*Pinjaman Saya*) dibungkus dalam satu blok kondisi `@if($this->canBorrow)`.
    - Mengonversi komentar HTML menjadi komentar Blade (`{{-- ... --}}`) untuk mencegah kebocoran teks pada DOM dan pengujian.
- **Verifikasi Pengujian Otomatis**:
  - Ditambahkan pengujian di `DocumentLoanTest`:
    - `test_user_without_document_borrowing_permission_cannot_see_catalog_and_my_loans_tabs`
    - `test_user_with_document_borrowing_permission_can_see_catalog_and_my_loans_tabs`
  - Seluruh rangkaian test suite (31 *test cases*, 140 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 47. Penyelarasan Desain & Interaktivitas Kartu Statistik (Bento Grid) Dashboard
- **Penyelarasan Visual Monokrom & Konsisten (`dashboard.blade.php`)**:
  - Menyeragamkan seluruh kartu statistik ke latar putih bersih (`bg-white border border-slate-200/90`) dengan tipografi seragam (`text-2xl font-bold text-slate-800`), menggantikan variasi kartu gradasi hijau tebal atau kartu kuning/merah pekat yang sebelumnya tidak berimbang.
  - Setiap kartu dilengkapi kontainer ikon dengan warna aksen lembut (*emerald, blue, purple, amber, rose*).
- **Interaktivitas Navigasi Langsung (*Clickable Stat Cards*)**:
  - Setiap kartu statistik kini bertindak sebagai tautan aktif (*hyperlink*) yang langsung membuka halaman operasional terkait saat diklik:
    - *Total Surat Masuk* ➔ `/registry/incoming` (`route('registry.incoming.index')`)
    - *Total Surat Keluar* ➔ `/registry/outgoing` (`route('registry.outgoing.index')`)
    - *Total Surat Khusus* ➔ `/registry/special` (`route('registry.special.index')`)
    - *Menunggu Upload* ➔ `/registry/scan-queue` (`route('registry.scan-queue')`)
    - *Peringatan Retensi* ➔ `/archive/destructions` (`route('archive.destructions')`)
  - Membersihkan kode tombol *Export Laporan* yang sebelumnya tidak aktif (*dead code*).
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (31 *test cases*, 140 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 48. Quick Actions Header Bar & Filter Pencarian Monitoring Arsip Dokumen Dashboard
- **Quick Actions Bar pada Header Dashboard (`dashboard.blade.php`)**:
  - Menyediakan tombol akses cepat di kanan header yang responsif dan terkontrol hak akses modul:
    - `+ Surat Masuk` (`input_incoming`) ➔ `/registry/incoming/create`
    - `+ No. Surat Keluar` (`input_outgoing`) ➔ `/registry/outgoing/create`
    - `+ No. Surat Khusus` (`input_internal`) ➔ `/registry/special/create`
  - Memaksimalkan efisiensi kerja staf langsung dari dashboard tanpa harus membuka menu navigasi sidebar.
- **Pencarian Cepat & Filter Status Monitoring (`Dashboard.php` & `dashboard.blade.php`)**:
  - Menambahkan properti `$monitoringSearch` dan `$monitoringFilter` (`all`, `waiting`, `archived`) pada komponen Livewire `Dashboard`.
  - Toolbar di atas tabel Monitoring Arsip dilengkapi:
    - Input pencarian cepat kata kunci (nomor surat, perihal, atau nama penerima) secara *real-time debounce*.
    - *Segmented Pill Filters*: `[ Semua ]`, `[ Menunggu ]`, dan `[ Selesai ]`.
  - Memberikan kemudahan bagi staf untuk memantau apakah naskah fisik yang didaftarkan sudah diarsipkan atau masih berstatus menunggu unggah.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (31 *test cases*, 140 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 49. Penyempurnaan Formulir Registrasi Surat Masuk (Klasifikasi, Tanggal Otomatis, & Toggle Sifat)
- **Penambahan Klasifikasi Arsip & Integrasi JRA (`Incoming\Create.php` & Blade)**:
  - Menambahkan dropdown input **Klasifikasi Arsip** (`classification_id`) pada Section 2 (Klasifikasi & Distribusi) yang memuat seluruh hierarki sub-klasifikasi aktif.
  - Perhitungan otomatis masa aktif Jadwal Retensi Arsip (JRA): Saat surat masuk disimpan, sistem otomatis menghitung `retention_start_date` dan `retention_end_date` berdasarkan `active_years` pada klasifikasi terpilih, sehingga surat masuk langsung terpantau dalam audit retensi dan kartu statistik dashboard.
- **Nilai Bawaan Tanggal Hari Ini (*Default Date UX*)**:
  - Tanggal surat (`document_date`) dan tanggal diterima (`received_date`) otomatis terisi tanggal hari ini saat form dimuat (`mount()`), mengurangi beban entri data manual staf.
- **Penyederhanaan Antarmuka (*Clean & Compact UI*)**:
  - Mengganti radio cards besar pada "Sifat Surat" menjadi **Segmented 3-Column Button Toggle** yang padat dan hemat ruang vertikal (Biasa, Penting, Rahasia).
  - Merapikan dropdown "Unit Tujuan" dengan mencantumkan nama, kode unit, dan kode divisi induk.
  - Membersihkan alert box flash message statis yang tidak efektif di atas form.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat test suite baru `tests/Feature/IncomingMailTest.php` (3 test cases, 17 assertions):
    - `test_incoming_create_mounts_with_default_dates_and_classifications`
    - `test_user_can_register_incoming_mail_with_classification_and_retention`
    - `test_incoming_mail_validation_fails_without_classification`
  - Seluruh rangkaian test suite (34 *test cases*, 157 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 50. Penyempurnaan Formulir Registrasi Surat Keluar (Default Divisi Akun, Tanggal Otomatis, & JRA)
- **Otomatisasi Default Divisi & Tanggal Hari Ini (`Outgoing\Create.php`)**:
  - Tanggal surat (`document_date`) otomatis terisi tanggal hari ini (`date('Y-m-d')`) saat form dimuat.
  - Dropdown Divisi Pengirim (`unit_id`) secara pintar langsung memilihkan divisi tempat akun staf yang login bernaung (`Auth::user()?->getDivision()?->id`).
- **Integrasi Jadwal Retensi Arsip (JRA)**:
  - Pada saat surat keluar disimpan dan diarsipkan (`createDocument()`), sistem otomatis menghitung `retention_start_date` dan `retention_end_date` berdasarkan `active_years` pada klasifikasi surat terpilih.
- **Penyederhanaan Antarmuka (Compact Sifat Toggle)**:
  - Mengganti kartu radio besar pada "Sifat Surat" menjadi **Segmented 3-Column Button Toggle** (Biasa, Penting, Rahasia) yang padat, modern, dan hemat ruang.
  - Memastikan form tetap bersih, minimalis, dan bebas dari elemen kartu estimasi nomor tambahan sesuai arahan pengguna.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (34 *test cases*, 157 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 51. Penyempurnaan Formulir Registrasi Surat Khusus (Default Departemen Akun, Tanggal Otomatis, Toggle Sifat, & JRA)
- **Otomatisasi Departemen Pengirim & Tanggal Hari Ini (`Internal\Create.php`)**:
  - Tanggal surat (`document_date`) otomatis terisi tanggal hari ini (`date('Y-m-d')`) saat form dimuat.
  - Dropdown Departemen Pengirim (`unit_id`) secara pintar memilihkan departemen tempat akun staf yang login bernaung (`Auth::user()?->unit?->id`), sehingga staf hanya perlu memilih Unit Tujuan dan Perihal.
- **Integrasi Jadwal Retensi Arsip (JRA)**:
  - Pada saat surat khusus disimpan dan diarsipkan (`createDocument()`), sistem otomatis menghitung `retention_start_date` dan `retention_end_date` berdasarkan `active_years` pada klasifikasi surat terpilih.
- **Penyederhanaan Antarmuka (Compact Sifat Toggle)**:
  - Mengganti kartu radio besar pada "Sifat Surat" menjadi **Segmented 3-Column Button Toggle** (Biasa, Penting, Rahasia) yang padat, modern, dan hemat ruang.
  - Menjaga form tetap bersih, minimalis, dan bebas dari elemen tambahan yang berlebihan sesuai arahan pengguna.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (34 *test cases*, 157 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 52. Fitur Pembatalan Mandiri Permohonan Pinjam Fisik & Pembersihan Header Peminjaman
- **Pembersihan Header Halaman (`document-loan-index.blade.php`)**:
  - Menghilangkan badge dekoratif statis *"Tapersip Beroperasi"* di sudut kanan atas agar tampilan header bersih, tenang, dan profesional.
- **Fitur Pembatalan Mandiri Pemohon (*Self-Cancel Request*)**:
  - Pada tab *Pinjaman Saya*, untuk setiap permohonan yang masih berstatus `Menunggu` (`requested`), ditambahkan tombol aksi **"Batal"** bersanding dengan tombol cetak tiket.
  - Memverifikasi kepemilikan pemohon sebelum membatalkan (`$loan->user_id === Auth::id()`).
  - Menambahkan status `'cancelled'` pada kolom enum tabel `document_loans` (`2026_10_15_000004_add_cancelled_to_document_loans_status_enum.php`).
  - Dokumen fisik yang dibatalkan otomatis kembali berstatus **Tersedia** di katalog tanpa menunggu aksi penolakan petugas Tapersip.
  - Menampilkan badge netral abu-abu `"Dibatalkan"` pada tab Pinjaman Saya dan Meja Kerja Sirkulasi Tapersip.
  - Mencatat aksi pembatalan ke dalam `AuditLog::record(...)`.
- **Verifikasi Pengujian Otomatis**:
  - Ditambahkan pengujian di `DocumentLoanTest::test_user_can_cancel_their_own_pending_loan_request`.
  - Seluruh rangkaian test suite (35 *test cases*, 162 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 53. Penyempurnaan Antrean Scan Tapersip (JRA Otomatis, Segmented Filter dengan Counter, & Hierarki Loker Modal)
- **Integrasi Jadwal Retensi Arsip (JRA) pada Finalisasi Scan (`ScanQueue.php`)**:
  - Saat petugas Tapersip mengunggah berkas scan fisik final (`saveFinalFile()`), sistem otomatis menghitung `retention_start_date` dan `retention_end_date` berdasarkan `active_years` klasifikasi naskah.
- **Segmented Filter Pills Berpenghitung Antrean (`scan-queue.blade.php`)**:
  - Mengganti dropdown filter jenis naskah dengan *Segmented Filter Pills* horizontal dengan badge counter langsung:
    `[ Semua (X) ]`, `[ Surat Keluar (Y) ]`, dan `[ Surat Khusus (Z) ]`.
  - Petugas dapat langsung melihat beban kerja antrean scan secara *real-time*.
- **Hierarki Jalur Loker pada Modal Unggah Scan**:
  - Dropdown Loker Penyimpanan menampilkan jalur induk berjenjang (`Induk / Loker`) untuk mencegah kesalahan penyimpanan berkas.
- **Keterbacaan Perihal Naskah**:
  - Mengganti pemotongan teks kaku `truncate` dengan text wrapping dan toggle expandable *Show More / Show Less* untuk memudahkan pencocokan fisik naskah.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (35 *test cases*, 162 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 54. Fitur Relokasi / Pemindahan Loker Berkas dengan Sinkronisasi Storage NAS Otomatis
- **Helper Transaksional pada Model Document (`Document::relocateFolder`)**:
  - Menyediakan method terpusat `$document->relocateFolder(?int $newFolderId, ?int $userId = null): bool` yang menangani sinkronisasi fisik dan database secara atomik.
  - Memeriksa keberadaan file fisik di storage NAS (`Storage::disk('nas')`) atau *fallback storage* lokal.
  - Menyiapkan direktori loker tujuan baru di storage NAS secara otomatis (`Storage::disk('nas')->makeDirectory($newDirectory)`).
  - Memindahkan berkas fisik di NAS (`Storage::disk('nas')->move($oldPath, $newPath)`) dan memperbarui kolom `file_path` pada `document_versions`.
  - Memperbarui kolom `folder_id` pada tabel `documents`.
  - Mencatat aksi pemindahan loker secara otomatis ke `AuditLog::record(...)` lengkap dengan nama loker asal dan nama loker tujuan.
- **Implementasi Titik Aksi pada 3 Halaman Explorer Arsip**:
  - **Arsip Surat Masuk (`Incoming\Index.php` & `incoming/index.blade.php`)**.
  - **Arsip Surat Keluar (`Outgoing\Index.php` & `outgoing/index.blade.php`)**.
  - **Arsip Surat Khusus (`Internal\Index.php` & `internal/index.blade.php`)**.
  - Pada kolom Aksi tabel arsip, ditambahkan tombol *Pindahkan Lokasi / Loker Berkas* (ikon folder berpindah berwarna kuning/amber) khusus bagi pengguna yang memiliki izin `archive` (Tapersip & Superadmin).
  - Modal pop-up `modal-move-document` menampilkan nomor dokumen, perihal, nama loker saat ini, serta pilihan dropdown loker tujuan baru berjalur hierarkis.
- **Verifikasi Pengujian Otomatis**:
  - Dibuat test suite baru `tests/Feature/RelocateDocumentTest.php` (2 test cases, 15 assertions):
    - `test_authorized_user_can_relocate_document_with_nas_file_sync` (memvalidasi pemindahan file fisik di storage NAS palsu, pembaruan database, dan pencatatan audit log).
    - `test_user_without_archive_permission_cannot_relocate_document` (memvalidasi proteksi otorisasi 403 bagi staf biasa).
  - Seluruh rangkaian test suite (37 *test cases*, 177 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 55. Custom Pop-Up Dialog Sukses Pengarsipan Surat Masuk (Success Action Modal Dialog)
- **Komponen Livewire (`Incoming\Create.php`)**:
  - Mengganti alur *redirect* instan dengan modal interaktif pasca-simpan: `$this->showSuccessModal = true`.
  - Menyimpan data ringkasan dokumen yang baru saja diarsipkan (`savedDocumentNumber`, `savedDocumentSubject`, `savedDocumentSender`, `savedDocumentUnitName`, `savedDocumentFolderName`).
  - Menyediakan 3 metode aksi cepat:
    - `resetFormAndInputAgain()`: Mereset seluruh field form secara instan dan mengembalikan tanggal hari ini agar staf dapat mencatat tumpukan surat masuk berikutnya tanpa meninggalkan halaman.
    - `redirectToIndex()`: Mengarahkan pengguna ke halaman daftar arsip surat masuk (`/registry/incoming`).
    - `finishAndRedirect()`: Mengarahkan pengguna kembali ke Dashboard utama.
- **Antarmuka Pengguna (`incoming/create.blade.php`)**:
  - Menambahkan modal pop-up beranimasi centang sukses hijau (*checkmark*).
  - Menampilkan kartu ringkasan naskah: Nomor surat, perihal, instansi pengirim, unit kerja tujuan, dan nama loker penyimpanan.
  - Menyediakan tombol aksi utama *"Input Surat Masuk Lagi"* serta pilihan *"Daftar Arsip"* dan *"Ke Dashboard"*.
- **Verifikasi Pengujian Otomatis**:
  - Diperbarui `tests/Feature/IncomingMailTest.php`:
    - `test_user_can_register_incoming_mail_with_classification_and_retention` (memvalidasi pembukaan modal sukses dan kelengkapan data ringkasan).
    - `test_user_can_reset_form_to_input_another_incoming_mail` (memvalidasi reset form untuk pencatatan beruntun).
  - Seluruh rangkaian test suite (38 *test cases*, 186 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 56. Standarisasi Custom Pop-Up Dialog Sukses Pengarsipan (Surat Masuk, Surat Keluar, & Surat Khusus)
- **Komponen Livewire (`Outgoing\Create.php` & `Internal\Create.php`)**:
  - Mengganti alur *redirect* instan pada aksi Tapersip *"Simpan & Arsipkan"* dengan modal interaktif pasca-simpan: `$this->showArchiveSuccessModal = true`.
  - Menyimpan data ringkasan dokumen yang baru saja diarsipkan langsung (`savedDocumentNumber`, `savedDocumentSubject`, `savedDocumentSender`, `savedDocumentRecipient`, `savedDocumentUnitName`, `savedDocumentFolderName`, `savedDocumentSignerLabel`).
  - Menyediakan 3 metode aksi cepat:
    - `resetFormAndInputAgain()`: Mereset seluruh field form secara instan dan mengembalikan tanggal hari ini serta default divisi/departemen akun staf agar dapat mencatat naskah berikutnya secara beruntun.
    - `redirectToIndex()`: Mengarahkan pengguna ke halaman daftar arsip masing-masing (`/registry/outgoing` dan `/registry/special`).
    - `finishAndRedirect()`: Mengarahkan pengguna kembali ke Dashboard utama.
- **Antarmuka Pengguna (`outgoing/create.blade.php` & `internal/create.blade.php`)**:
  - Menambahkan modal pop-up beranimasi centang sukses hijau (*checkmark*).
  - Menampilkan kartu ringkasan naskah lengkap: Nomor surat resmi, perihal, instansi/unit pengirim, tujuan/penerima, penandatangan, dan loker penyimpanan.
  - Menyediakan tombol aksi utama *"Input Surat Keluar/Khusus Lagi"* serta pilihan *"Daftar Arsip"* dan *"Ke Dashboard"*.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (38 *test cases*, 186 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 57. Penetapan Opsi Netral Pilihan Jenis Dokumen pada Formulir Surat Khusus
- **Penghapusan Auto-Default "Memo" (`app/Livewire/Registry/Internal/Create.php`)**:
  - Menghapus pra-seleksi otomatis jenis dokumen `Memo` pada lifecycle `mount()`, sehingga properti `$document_type_id` berstatus netral/kosong saat form pertama kali dimuat.
  - Memastikan pengguna secara sadar memilih jenis dokumen yang relevan (seperti Surat Perintah, Surat Keputusan, Memo, dll.) sesuai kebutuhan naskah dinas.
  - Menambahkan `'document_type_id'` ke dalam array reset pada method `resetFormAndInputAgain()` agar saat aksi *"Input Surat Khusus Lagi"* ditekan, dropdown jenis dokumen kembali kosong ke placeholder default.
- **Penyempurnaan Antarmuka Form (`resources/views/livewire/registry/internal/create.blade.php`)**:
  - Menambahkan indikator tanda bintang merah `<span class="text-red-500">*</span>` pada label *"Jenis Dokumen"* untuk mempertegas bahwa pilihan ini wajib diisi.
  - Mempertahankan opsi placeholder `"-- Pilih Jenis Dokumen --"` sebagai pilihan pertama.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite (38 *test cases*, 186 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 58. Indikator Konektivitas & Health-Check Storage NAS pada Antrean Scan Tapersip
- **Layanan Pengecekan Terpusat (`App\Services\NasHealthService`)**:
  - Menyediakan method `NasHealthService::check(bool $forceRefresh = false): array` untuk memverifikasi kesiapan storage NAS secara aman tanpa risiko *fatal error* atau *blocking hang*.
  - Melakukan uji tulis dan baca file detak jantung (*transient heartbeat file*: `.healthcheck_*.tmp`) langsung ke disk `Storage::disk('nas')`.
  - Membaca kapasitas sisa storage via `disk_free_space()` dan `disk_total_space()` serta mengonversinya ke format yang mudah dibaca (*free_space*, misal: "120.5 GB").
  - Menerapkan *caching* 30 detik (`Cache::remember`) guna menjaga performa kueri livewire saat staf memfilter antrean atau mengetik kata kunci pencarian.
- **Backend Komponen Livewire (`App\Livewire\Registry\ScanQueue`)**:
  - Menyediakan computed property `$this->nasStatus` yang dikirim ke view.
  - Menyediakan method aksi `refreshNasConnection()` untuk memungkinkan petugas melakukan cek ulang status jaringan NAS secara manual disertai flash notifikasi.
  - **Safety Interlock**: Pada method `saveFinalFile()`, sistem melakukan verifikasi paksa real-time status NAS sebelum menyimpan berkas fisik. Jika NAS offline/terputus, eksekusi dihentikan dengan aman dan pesan kesalahan ramah ditampilkan pada formulir tanpa menyebabkan crash atau data korup.
- **Antarmuka Pengguna (`resources/views/livewire/registry/scan-queue.blade.php`)**:
  - **Header Pill Badge**: Ditempatkan di kanan atas judul halaman:
    - *Status Online*: Badge hijau lembut beranimasi titik detak jantung (*pulsing green dot*), teks `NAS Online`, info sisa kapasitas penyimpanan, dan tombol refresh ber-spinner halus.
    - *Status Offline*: Badge merah tegas, teks `NAS Terputus`, keterangan `Hubungi Administrator`, dan tombol cepat `Cek Lagi`.
  - **Modal Safety Interlock**:
    - Banner peringatan merah muncul di dalam modal verifikasi unggah scan jika koneksi NAS terputus.
    - Tombol *"Simpan & Arsipkan Permanen"* otomatis dinonaktifkan (*disabled*) untuk mencegah kegagalan unggah.
- **Verifikasi Pengujian Otomatis (`tests/Feature/NasStorageTest.php`)**:
  - Menambahkan 3 unit & feature test:
    - `test_nas_health_service_detects_online_status_and_metrics`
    - `test_scan_queue_renders_nas_online_badge_and_allows_manual_refresh`
    - `test_scan_queue_prevents_upload_and_shows_error_when_nas_is_offline`
  - Seluruh rangkaian test suite proyek (41 *test cases*, 196 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 59. Penyelarasan Nama Jenis Dokumen Dinamis pada Metadata Audit Trail Surat Khusus
- **Pencatatan Log Surat Khusus (`app/Livewire/Registry/Internal/Create.php`)**:
  - Pada method `bookNumber()` dan `saveAndArchive()`, metadata `type` tidak lagi menggunakan string hardcode `"internal"`.
  - Sistem secara dinamis mengambil nama jenis naskah riil yang dipilih pengguna dari relasi master data:
    `$docTypeName = $this->documentTypes->firstWhere('id', (int) $this->document_type_id)?->name ?? \App\Models\DocumentType::find($this->document_type_id)?->name ?? 'Surat Khusus'`.
  - Metadata `AuditLog::record(...)` kini mencatat nama naskah resmi, seperti `"Memo Internal"`, `"Surat Perintah"`, `"Surat Tugas"`, `"Surat Keputusan"`, dll.
- **Penyelarasan pada Antrean Scan Tapersip (`app/Livewire/Registry/ScanQueue.php`)**:
  - Pada method `saveFinalFile()` saat pengunggahan scan PDF fisik arsip, metadata `type` otomatis diselaraskan menggunakan nama jenis dokumen riil (`$document->documentType?->name ?? 'Surat Khusus'`) alih-alih tipe mentah database (`'internal'`).
- **Kompatibilitas Log Riwayat Lama / Backward-Compatibility (`audit-trail-index.blade.php` & `AuditTrailIndex.php`)**:
  - Pada `AuditTrailIndex::render()`, relasi dokumen di-eager load hingga jenis naskah: `AuditLog::with(['user', 'document.documentType'])`.
  - Pada tabel Audit Trail kolom *Detail Referensi*, jika rekaman lama masih menyimpan `type === 'internal'`, antarmuka secara otomatis me-resolve menjadi nama jenis dokumen asli dari dokumen terkait (`$log->document?->documentType?->name ?? 'Surat Khusus'`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DashboardActivityLogTest.php`)**:
  - Menambahkan 3 pengujian:
    - `test_special_document_booking_records_selected_document_type_name_in_audit_log_metadata` (memvalidasi pencatatan nama jenis naskah dinamis pada booking Surat Khusus).
    - `test_scan_queue_records_document_type_name_in_audit_log_metadata_for_internal_document` (memvalidasi pencatatan nama jenis naskah saat unggah scan).
    - `test_audit_trail_index_renders_document_type_for_backward_compatible_internal_logs` (memvalidasi bahwa tampilan Audit Trail me-render nama jenis dokumen dan tidak lagi menampilkan `{"type":"internal"}`).
  - Seluruh rangkaian test suite proyek (44 *test cases*, 208 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 60. Penyelarasan Badge Jenis Dokumen Dinamis pada Tabel Monitoring Arsip Dashboard
- **Antarmuka Pengguna (`resources/views/livewire/dashboard.blade.php`)**:
  - Pada tabel *Monitoring Arsip Dokumen* (kolom *Nomor & Jenis Dokumen*), badge naskah untuk kategori surat khusus tidak lagi menampilkan teks statis `"Surat Khusus"`.
  - Sistem menampilkan nama jenis naskah spesifik yang dipilih oleh pengguna saat pembuatan dokumen:
    `{{ $doc->documentType?->name ?? 'Surat Khusus' }}` (seperti *"Memo Internal"*, *"Surat Perintah"*, *"Surat Tugas"*, *"Surat Keputusan"*, dll.).
  - Memanfaatkan relasi `documentType` yang sudah di-eager load pada kueri `$monitoringQuery` di `Dashboard.php` sehingga tidak memicu query tambahan (zero additional DB queries).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DashboardActivityLogTest.php`)**:
  - Menambahkan test case `test_dashboard_monitoring_table_displays_specific_document_type_name_for_internal_documents` yang memvalidasi bahwa tabel monitoring di Dashboard menampilkan badge nama jenis dokumen yang dipilih (misal: *"Surat Tugas"*).
  - Seluruh rangkaian test suite proyek (45 *test cases*, 210 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 61. Penyelarasan Badge Jenis Dokumen Dinamis pada Kolom Identitas Dokumen Sirkulasi & Peminjaman Berkas
- **Komponen Livewire (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Memastikan relasi `documentType` selalu di-eager load pada kueri:
    - Tab 1 Katalog: `Document::with(['folder', 'unit', 'documentType', 'activeLoan.user'])`.
    - Tab 2 Pinjaman Saya: `DocumentLoan::with(['document.folder', 'document.unit', 'document.documentType', 'officer'])`.
    - Tab 3 Meja Sirkulasi: `DocumentLoan::with(['document.folder', 'document.unit', 'document.documentType', 'user.unit', 'officer'])`.
  - Mencegah masalah query N+1 saat mengakses nama jenis dokumen di berbagai tab peminjaman.
- **Antarmuka Pengguna (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Pada tabel Katalog Arsip (*Tab 1*) kolom **"Identitas Dokumen"**, badge naskah tidak lagi bertuliskan statis `"Surat Khusus"`.
  - Sistem menampilkan nama jenis naskah riil yang dipilih pengguna:
    `{{ $doc->type === 'incoming' ? 'Surat Masuk' : ($doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus')) }}`.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan pengujian `test_catalog_table_displays_specific_document_type_name_for_internal_documents` yang memvalidasi bahwa tabel katalog menampilkan badge nama jenis dokumen naskah dinas (misal: *"Surat Perintah"*) bukan sekadar label teknis.
  - Seluruh rangkaian test suite proyek (46 *test cases*, 212 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 62. Penyelarasan Nama Jenis Dokumen Dinamis pada Antrean Scan Tapersip
- **Komponen Livewire (`app/Livewire/Registry/ScanQueue.php`)**:
  - Pada method `openUploadModal($id)`, kueri dokumen kini meng-eager load `documentType`: `Document::with(['createdBy', 'documentType'])->findOrFail($id)`.
  - Properti `$uploadDocType` disetel secara dinamis: `$this->uploadDocType = $doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus')`.
  - Memastikan modal verifikasi unggah scan (`modal-upload-queue`) pada bagian *Identitas Dokumen -> Jenis* menampilkan jenis naskah spesifik yang dipilih pengguna (misal: *"Surat Tugas"*, *"Surat Perintah"*, *"Memo Internal"*, dll.).
- **Antarmuka Pengguna (`resources/views/livewire/registry/scan-queue.blade.php`)**:
  - Pada tabel antrean scan kolom **"Identitas Dokumen"**, label jenis naskah tidak lagi bertuliskan statis `"Surat Khusus"`.
  - Teks kini menampilkan nama jenis naskah riil: `{{ $doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus') }}`.
- **Verifikasi Pengujian Otomatis (`tests/Feature/NasStorageTest.php`)**:
  - Menambahkan pengujian `test_scan_queue_table_and_modal_display_specific_document_type_name_for_internal_documents` yang memvalidasi bahwa tabel antrean scan maupun modal verifikasi unggah menampilkan nama jenis dokumen naskah dinas.
  - Seluruh rangkaian test suite proyek (47 *test cases*, 215 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 63. Penerapan Custom Pagination Standar pada Seluruh Tab Halaman Cari & Pinjam Surat
- **Antarmuka Pengguna (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Mengganti pemanggilan paginasi bawaan Tailwind (`->links()`) dengan tampilan komponen paginasi kustom resmi proyek (`->links('vendor.pagination.custom')`) pada ketiga tab:
    1. **Tab 1: Katalog & Cari Surat** (`$catalogDocuments->links('vendor.pagination.custom')`).
    2. **Tab 2: Pinjaman Saya** (`$myLoans->links('vendor.pagination.custom')`).
    3. **Tab 3: Sirkulasi & Persetujuan Tapersip** (`$circulationLoans->links('vendor.pagination.custom')`).
  - Menghasilkan pengalaman pengguna yang selaras dan seragam dengan halaman explorer arsip lainnya:
    - Informasi rekap bahasa Indonesia: *"Menampilkan X – Y dari Z data"*.
    - Tombol navigasi mandiri (*Prev / Next*) dengan ikon panah tebal dan tombol nomor halaman aktif berlatar warna primer brand (`bg-[var(--color-primary)]`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan pengujian `test_catalog_table_renders_custom_pagination_view` yang memvalidasi bahwa tampilan paginasi kustom (`vendor.pagination.custom`) ter-render dengan benar saat jumlah data melebihi batas per halaman.
  - Seluruh rangkaian test suite proyek (48 *test cases*, 219 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 64. Notifikasi Email Otomatis Pengarsipan Berkas Fisik Kepada Staf Pembuat Naskah
- **Kelas Notifikasi (`App\Notifications\DocumentArchivedNotification`)**:
  - Menggunakan arsitektur `Notification` bawaan Laravel (`Illuminate\Notifications\Notification`).
  - Menerima relasi naskah `$document`, petugas pengarsip `$officer`, dan versi berkas `$version`.
  - Format subjek email: `"[E-Arsip] Dokumen {$document->number} Telah Selesai Diarsipkan"`.
  - Menyediakan tautan langsung menuju pratinjau berkas digital PDF di sistem (`route('document.preview', $version->id)`).
- **Desain Template Email HTML Responsif (`resources/views/emails/document-archived.blade.php`)**:
  - Dirancang dengan standar *Enterprise Clean Layout* dengan aksen warna brand (`#059669` / emerald), kompatibel penuh dengan klien email modern dan Outlook (POP/SMTP/M365).
  - Memuat:
    - Header resmi repositori digital (*E-Arsip Repository*).
    - Status hero badge centang hijau *"Selesai Diarsipkan"*.
    - Sapaan personal kepada staf pendaftar (`$recipientName`).
    - Kartu ringkasan naskah dinas: Nomor surat (font monospace), nama jenis dokumen riil (misal: *Surat Perintah*, *Memo Internal*, *Surat Keluar*), perihal, jalur loker penyimpanan fisik, tanggal naskah, nama petugas Tapersip, dan cap waktu arsip.
    - Tombol aksi utama (*Call-to-Action*): *"Buka & Pratinjau Dokumen PDF"*.
    - Footer berisi penafian kerahasiaan dan notifikasi otomatis.
- **Penyelarasan Komponen Antrean Scan (`app/Livewire/Registry/ScanQueue.php`)**:
  - Pada method `saveFinalFile()`, sistem secara otomatis mendeteksi akun staf pembuat naskah (`$document->createdBy`).
  - **Aturan Bisnis Eksklusif**: Email **hanya** dikirimkan kepada user pemilik naskah yang bersangkutan (`$document->createdBy`), bukan staf lain.
  - **Mekanisme Fail-Safe (Non-blocking)**: Pengiriman dibungkus dalam blok proteksi `try ... catch (\Throwable $e)` yang mencatat peringatan ke `Log::warning(...)` tanpa pernah membatalkan atau menggagalkan penyimpanan berkas fisik di NAS/database jika terjadi kendala pada server SMTP.
  - Flash message sukses disempurnakan: *"File PDF Final berhasil diunggah dan diarsipkan. Notifikasi email telah dikirim ke [Nama Staf]."*
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentArchivedNotificationTest.php`)**:
  - Menambahkan 3 test case lengkap:
    1. `test_document_creator_receives_email_notification_when_tapersip_uploads_file`: Memvalidasi bahwa notifikasi `DocumentArchivedNotification` terkirim tepat ke staf pembuat naskah saat admin mengarsipkan.
    2. `test_other_users_do_not_receive_email_notification`: Memvalidasi bahwa staf lain sama sekali tidak menerima notifikasi untuk naskah yang bukan miliknya.
    3. `test_email_view_renders_correct_document_details`: Memvalidasi kelengkapan teks rendered HTML email (nomor surat, perihal, jenis dokumen, nama staf, loker, nama petugas, dan tautan pratinjau).
  - Seluruh rangkaian test suite proyek (51 *test cases*, 234 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 65. Optimasi Performa Upload Berkas Scan Tapersip dengan Asynchronous Email Deferral
- **Akar Masalah**:
  - Saat Admin Tapersip menekan *"Simpan & Arsipkan Permanen"*, browser mengalami loading lama (4–10 detik) karena proses pengiriman email notifikasi via SMTP hosting dijalankan secara sinkron (*blocking*), sehingga browser harus menunggu proses handshake SSL/TLS dan respons server SMTP selesai sebelum modal tertutup.
- **Implementasi Solusi (`app/Livewire/Registry/ScanQueue.php`)**:
  - Mengintegrasikan fungsi modern Laravel 11 `defer(function () { ... })` pada method `saveFinalFile()`.
  - Livewire menyelesaikan penyimpanan file ke NAS, update database, dan pencatatan audit log dalam hitungan milidetik, lalu langsung mengirimkan respons penutupan modal dan flash notifikasi ke layar Admin Tapersip secara **instan (< 0.5 detik)**.
  - Proses pengiriman email notifikasi via SMTP dieksekusi secara asinkron di latar belakang (*background lifecycle*) setelah respons HTTP dikirim ke browser.
  - Menjaga kompatibilitas lingkungan testing (`app()->runningUnitTests()`) agar pengujian otomatis tetap dapat memverifikasi pengiriman notifikasi tanpa kendala.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentArchivedNotificationTest.php`)**:
  - Seluruh pengujian notifikasi email dan pengarsipan berkas lulus 100%.
  - Seluruh rangkaian test suite proyek (51 *test cases*, 234 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 66. Resolusi Anomali Penomoran Surat Keluar & Pengamanan Urutan Sequence Otomatis
- **Akar Masalah**:
  - Saat digitalisasi dokumen lama melalui aksi *"Simpan & Arsipkan"*, input nomor manual (contoh: `123456789/pms/pms/2026`) diekstrak angka pertamanya oleh regex dan disimpan mentah ke dalam kolom integer `sequence_number = 123456789`.
  - Ketika staf berikutnya mengklik *"Ambil Nomor Surat"*, kueri `MAX('sequence_number')` mendeteksi angka tersebut sebagai urutan tertinggi tahun ini, sehingga sistem otomatis menerbitkan nomor berikutnya yang melompat: `123456790` dan `123456791`.
- **Implementasi Pengamanan Kode Backend (`Outgoing/Create.php` & `Internal/Create.php`)**:
  - **Pencegahan Kontaminasi Sequence Manual**: Pada method `saveAndArchive()`, angka yang diekstrak dari nomor fisik manual diverifikasi agar tidak melebihi batas wajar urutan nomor kantor tahunan (`$parsedSequence <= 99999`). Jika berupa nomor barcode/dokumen luar yang panjang, kolom `sequence_number` disimpan sebagai `null` (teks nomor surat fisik tetap tersimpan utuh di kolom `number`).
  - **Kueri Defensif Penomoran Otomatis**: Pada method `generateNumber()`, pencarian nilai maksimum dibatasi hanya pada nomor urut wajar (`->where('sequence_number', '<=', 99999)`), sehingga jika terdapat dokumen dengan nomor panjang di database, urutan penomoran otomatis kantor tetap berjalan normal dari nomor urut kantor yang sah.
- **Pembersihan Data Anomali di Database**:
  - Menyetel `sequence_number` pada dokumen ID 32 menjadi `null`.
  - Mengembalikan nomor dan urutan dokumen ID 39 menjadi `24` (`24/OPS-PP.00/10/2026`).
  - Mengembalikan nomor dan urutan dokumen ID 40 menjadi `25` (`25/OPS-PP.01/10/2026`).
  - Pengambilan nomor berikutnya otomatis kembali normal melanjutkan nomor urut **`26`** (`26/OPS-.../10/2026`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/SequenceNumberingTest.php`)**:
  - Menambahkan test case `test_manual_large_document_number_does_not_pollute_auto_sequence_numbering` yang memvalidasi bahwa pengarsipan nomor manual panjang (> 99999) tidak mengotori `sequence_number` dan penerbitan nomor otomatis staf tetap melanjutkan urutan normal kantor.
  - Seluruh rangkaian test suite proyek (52 *test cases*, 241 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 67. Penyesuaian Desain Minimalis & Subjek Email Notifikasi Pengarsipan Dokumen
- **Penyesuaian Subjek Email (`App\Notifications\DocumentArchivedNotification`)**:
  - Menghapus prefix tag `[E-Arsip]` pada subjek email pemberitahuan sehingga format subjek menjadi bersih, ringkas, dan profesional:
    `"Dokumen {$this->document->number} Telah Selesai Diarsipkan"`.
- **Penyederhanaan Template Email (`resources/views/emails/document-archived.blade.php`)**:
  - **Penghapusan Baris Loker**: Menghapus baris detail *Loker Penyimpanan* dari kartu rincian naskah email agar staf pembuat fokus pada status resmi pengarsipan tanpa perlu mengetahui lokasi lemari penyimpanan fisik internal Tapersip.
  - **Penghapusan Tombol Call-to-Action (CTA)**: Menghapus tombol *"Buka & Pratinjau Dokumen PDF"* (`.btn-container`) sehingga email murni berfungsi sebagai surat pemberitahuan resmi (*clean notification-only email*).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentArchivedNotificationTest.php`)**:
  - Memperbarui `test_email_view_renders_correct_document_details` untuk memvalidasi:
    - Subjek email bersih tanpa prefix tag `[E-Arsip]`.
    - Tidak memuat baris teks *"Loker Penyimpanan"*.
    - Tidak memuat tombol tautan pratinjau.
  - Seluruh rangkaian test suite proyek (52 *test cases*, 242 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 68. Notifikasi Real-Time Web ke PC Desktop untuk Sirkulasi Peminjaman Berkas Fisik Tapersip
- **Komponen Listener Cerdas (`App\Livewire\Components\TapersipNotificationListener`)**:
  - Komponen latar belakang ringan yang khusus aktif untuk pengguna dengan hak akses Tapersip (`auth()->user()->isTapersip()`).
  - Menjalankan smart polling interval 8 detik (`wire:poll.8s="checkLoanEvents"`) untuk mendeteksi:
    1. **Pengajuan Pinjam Baru (`status = 'requested'`)**: Memeriksa tiket pinjaman baru berdasarkan selisih ID tertinggi (`lastKnownRequestId`).
    2. **Pembatalan Pinjaman (`status = 'cancelled'`)**: Memeriksa tiket yang dibatalkan staf berdasarkan `updated_at >= lastCheckedAt`.
  - Menerapkan mekanisme pelacakan ID dan timestamp terakhir yang telah diberitahukan guna mencegah peringatan berulang (*anti-duplicate alert logic*).
  - Mengirimkan event browser `desktop-notify` yang memuat judul, ringkasan pemohon/naskah/kode tiket, dan URL navigasi menuju meja sirkulasi Tapersip.
- **Antarmuka Pengguna & Integrasi Native Desktop PC (`app.blade.php` & `tapersip-notification-listener.blade.php`)**:
  - **Native OS Web Notification**: Memanfaatkan HTML5 Web Notification API untuk memunculkan banner notifikasi Windows di sudut layar PC. Banner dapat diklik untuk langsung mengarahkan peramban ke halaman Sirkulasi Tapersip (`/borrowings`), bahkan saat browser sedang diminimalkan atau membuka aplikasi lain.
  - **Audio Chime Mandiri**: Membunyikan suara bel lonceng lembut (*two-tone chime harmonic*) menggunakan Web Audio API murni secara instan tanpa membutuhkan file aset audio eksternal.
  - **Tombol Izin di Navbar Header**:
    - Menyediakan tombol saklar status di header navigasi: badge amber *"Aktifkan Notifikasi PC"* (jika izin browser belum aktif) yang otomatis berubah menjadi badge hijau *"Notifikasi PC Aktif"* dengan lampu indikator berdenyut (*pulsing green dot*).
- **Verifikasi Pengujian Otomatis (`tests/Feature/TapersipRealTimeNotificationTest.php`)**:
  - Menambahkan 4 test case lengkap:
    1. `test_listener_dispatches_desktop_notify_when_new_loan_is_requested` (memvalidasi trigger event saat staf mengajukan pinjam berkas).
    2. `test_listener_dispatches_desktop_notify_when_loan_is_cancelled` (memvalidasi trigger event saat staf membatalkan pinjaman).
    3. `test_non_tapersip_user_does_not_poll_or_dispatch_notifications` (memvalidasi proteksi otorisasi bagi staf biasa).
    4. `test_listener_does_not_repeat_notifications_for_already_notified_loans` (memvalidasi pencegahan notifikasi duplikat).
  - Seluruh rangkaian test suite proyek (56 *test cases*, 247 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 69. Penyempurnaan Notifikasi Real-Time: Icon Lonceng, Dropdown Panel, Floating Toast & Auto-Update Tabel Sirkulasi
- **Penghapusan Dependensi Pop-Up Windows OS**:
  - Menghapus ketergantungan pada Web Notification API browser (`new Notification(...)`) yang otomatis diblokir oleh peramban modern (Chrome, Brave) saat diakses via alamat IP jaringan lokal non-HTTPS (`http://192.168.x.x`).
- **Antarmuka Header: Icon Lonceng & Dropdown Panel (`tapersip-notification-listener.blade.php`)**:
  - Mengganti tombol teks *"Aktifkan Notifikasi PC"* dengan **Tombol Icon Lonceng Notifikasi** di navbar header.
  - Dilengkapi badge counter merah beranimasi denyut (*pulsing red badge*) yang menampilkan jumlah permohonan pinjam yang sedang menunggu tindakan (`pendingCount`).
  - Saat icon lonceng diklik, muncul dropdown interaktif berisikan daftar riwayat pengajuan pinjam baru dan pembatalan tiket terbaru, lengkap dengan nama staf, unit kerja, kode tiket, nomor dokumen, waktu relatif, dan tombol cepat *"Buka Meja Kerja Sirkulasi Tapersip &rarr;"*.
- **Floating In-App Visual Toast (`tapersip-notification-listener.blade.php`)**:
  - Saat ada staf mengajukan pinjam atau membatalkan pinjaman:
    - Membunyikan suara bel harmoni lonceng dua nada (*two-tone audio chime*) via Web Audio API murni.
    - Menampilkan kartu notifikasi melayang (*floating in-app toast*) di sudut kanan atas layar peramban (`fixed top-5 right-5 z-[9999]`) dengan animasi geser (*slide-in*), memuat judul, detail pemohon, kode tiket, tombol aksi *"Tinjau Meja Sirkulasi"*, dan tombol tutup mandiri (otomatis menghilang setelah 7 detik).
- **Sinkronisasi Tabel Real-Time Tanpa Refresh (`DocumentLoanIndex.php` & `document-loan-index.blade.php`)**:
  - Menambahkan pemancaran event global Livewire `$this->dispatch('loan-status-changed')` pada komponen listener saat ada transaksi baru atau pembatalan.
  - Menghubungkan listener `#[On('loan-status-changed')]` pada komponen `DocumentLoanIndex` untuk me-render ulang tabel sirkulasi secara otomatis.
  - Menambahkan pengaman `wire:poll.10s` pada kontainer meja kerja sirkulasi Tapersip sehingga tabel selalu ter-update secara otomatis saat petugas standby.
- **Verifikasi Pengujian Otomatis (`tests/Feature/TapersipRealTimeNotificationTest.php`)**:
  - Menambahkan pengujian `test_document_loan_index_listens_to_loan_status_changed_event` dan `test_listener_renders_notification_bell_and_dropdown_items`.
  - Seluruh rangkaian test suite proyek (58 *test cases*, 253 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 70. Redesain Minimalis & Pembersihan Tata Letak Dropdown Notifikasi Sirkulasi Tapersip
- **Pembersihan Elemen Visual yang Bertumpuk & Terhimpit (`tapersip-notification-listener.blade.php`)**:
  - Memperluas lebar kontainer dropdown menjadi `w-80 sm:w-[390px] md:w-[420px]` agar seluruh baris teks dan kode nomor tiket tidak lagi patah atau terpotong sempit.
  - **Penyempurnaan Header Dropdown**:
    - Judul panel dibuat ringkas dan proporsional dalam satu baris: `"Sirkulasi & Peminjaman Berkas"`.
    - Merapikan badge antrean `{{ $pendingCount }} Menunggu` dengan pill warna kuning/amber rapi tanpa baris terputus.
    - Menghilangkan teks dekoratif *"Live Sync"* yang sebelumnya membuat area header sesak.
  - **Redesain Daftar Notifikasi**:
    - Menghilangkan ikon lingkaran biru `+` besar yang tidak relevan.
    - Baris 1: Menampilkan badge status minimalis (*Permohonan Baru* beraksen hijau / *Dibatalkan* beraksen merah lembut) bersanding dengan stempel waktu relatif.
    - Baris 2: Menampilkan nama pemohon tebal (*bold*) beserta divisi/unit kerja dalam format yang rapi dan tidak tumpang tindih.
    - Baris 3: Kode tiket resmi (`loan_code`) ditampilkan dengan font monospace tebal warna primer, dipisahkan oleh titik tengah (`•`) dengan nomor surat terkait tanpa ada patahan baris nomor.
  - **Penyelarasan Icon Lonceng Header**:
    - Menata posisi badge counter merah di sudut kanan atas icon lonceng (`-top-0.5 -right-0.5`) sehingga pas dan tidak bergeser aneh di navbar.
- **Verifikasi Pengujian Otomatis (`tests/Feature/TapersipRealTimeNotificationTest.php`)**:
  - Seluruh pengujian komponen listener dan integrasi sirkulasi terkonfirmasi **lulus 100% (58 test cases, 253 assertions PASS)**.

### 71. Penggabungan Notifikasi Multi-Kategori (Upload Scan & Pinjam Berkas), Unread Dismiss, & Efek Sorot "Iniloh Data yang Kamu Cari"
- **Komponen Backend Terpadu (`App\Livewire\Components\TapersipNotificationListener`)**:
  - **Penggabungan Multi-Kategori Tugas Kearsipan**:
    1. *Antrean Upload Scan Fisik*: Memantau dokumen naskah yang baru diambil nomornya oleh staf dan berstatus `waiting_for_file` (menunggu scan).
    2. *Sirkulasi Peminjaman Berkas*: Memantau pengajuan tiket pinjam baru (`requested`) dan pembatalan tiket (`cancelled`).
  - **Mekanisme State Belum Dibaca / Unread Dismiss**:
    - Riwayat item yang sudah diklik/dibaca disimpan dalam cache per user (`tapersip_read_notifs_{user_id}`) selama 30 hari.
    - Menyediakan method `dismissAndRedirect($key, $targetUrl)`: Saat notifikasi diklik, item tersebut langsung hilang seketika dari daftar dropdown dan peramban diarahkan ke halaman tujuan.
    - Menyediakan method `markAllAsRead()`: Membersihkan seluruh notifikasi aktif dalam 1 klik.
  - **Penghitungan Badge Merah Akurat**:
    - `$pendingCount` menghitung seluruh tugas aktif yang belum dibaca (`unread_scans + unread_loans`), sehingga badge merah pada icon lonceng selalu akurat dan menyala saat ada tugas tertunda.
- **Antarmuka Header & Dropdown Notifikasi (`tapersip-notification-listener.blade.php`)**:
  - **Penamaan Header**: Mengganti teks panjang lama menjadi judul ringkas dan tegas: **`Notifikasi`**.
  - **Badge Counter Merah Mencolok**: Posisi koordinat badge merah dipasang presisi di dalam tombol lonceng (`top-1 right-1 z-10`) dengan efek ping denyut (*pulsing dot*) yang jelas dan tidak terpotong header navbar.
  - **Penanda Belum Dilihat (*Unread Dot*)**: Menambahkan titik bulat biru menyala (`bg-blue-600`) di sebelah kiri setiap notifikasi yang belum diklik.
  - **Pembedaan Kategori Tugas**:
    - *Upload Scan*: Badge oranye/amber lembut + nomor dokumen + staf pendaftar.
    - *Pinjam Fisik*: Badge biru/emerald + kode tiket + pemohon.
    - *Batal Pinjam*: Badge merah/rose + tiket yang dibatalkan.
  - **Tombol "Tandai Semua Dibaca ✓"**: Tersemat rapi di sudut kanan atas header dropdown.
- **Fitur Spotlight Focus & Auto-Scroll ("📍 Iniloh Data Yang Kamu Cari")**:
  - **Pada Halaman Antrean Scan (`resources/views/livewire/registry/scan-queue.blade.php`)**:
    - Menggunakan parameter URL `?highlight=doc-{id}`.
    - Saat halaman dimuat dari notifikasi, layar peramban otomatis bergulir halus (*smooth auto-scroll*) ke tengah baris dokumen yang dituju.
    - Baris tabel otomatis bersorot dengan bingkai hijau berdenyut (`ring-2 ring-emerald-500 bg-emerald-50/90 shadow-md`) dan memunculkan badge animasi: **`📍 Iniloh Data Yang Kamu Cari`** selama 4.5 detik.
  - **Pada Halaman Sirkulasi Peminjaman (`DocumentLoanIndex.php` & `document-loan-index.blade.php`)**:
    - Mendukung query parameter `?tab=circulation&highlight=loan-{id}` sehingga otomatis membuka tab Persetujuan Tapersip.
    - Baris tiket pinjaman yang dituju otomatis bergulir ke tengah layar dengan bingkai biru berdenyut (`ring-2 ring-blue-500 bg-blue-50/90 shadow-md`) dan badge animasi: **`📍 Iniloh Data Yang Kamu Cari`**.
- **Verifikasi Pengujian Otomatis (`tests/Feature/TapersipRealTimeNotificationTest.php` & `DocumentLoanTest.php`)**:
  - Menambahkan pengujian untuk multi-kategori antrean scan, dismiss and redirect, unread counter reduction, serta aktivasi tab sirkulasi via query parameter.
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 72. Penyederhanaan Efek Sorot Deep-Linking Ala Windows Explorer
- **Penghapusan Badge Stiker Animasi**:
  - Menghilangkan badge teks animasi `"📍 Iniloh Data Yang Kamu Cari"` pada tabel Antrean Scan (`scan-queue.blade.php`) dan tabel Meja Sirkulasi (`document-loan-index.blade.php`).
- **Implementasi Seleksi Baris Minimalis & Bersih**:
  - Mengadopsi gaya seleksi file pada Windows File Explorer:
    - **Antrean Scan**: Baris dokumen yang dituju otomatis bergulir ke tengah layar (*smooth auto-scroll*) dengan gaya seleksi bersih: latar lembut `bg-emerald-50/80`, aksen border kiri tegas `border-l-4 border-l-emerald-600`, dan outline halus `ring-1 ring-emerald-200/80`.
    - **Meja Sirkulasi Tapersip**: Baris tiket sirkulasi yang dituju otomatis bergulir ke tengah layar dengan gaya seleksi bersih: latar lembut `bg-blue-50/80`, aksen border kiri tegas `border-l-4 border-l-blue-600`, dan outline halus `ring-1 ring-blue-200/80`.
  - Efek seleksi bertahan selama 5 detik untuk mengarahkan fokus pandangan pengguna, kemudian memudar kembali secara halus (*fade transition*) ke tampilan baris tabel normal.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 73. Penetrasi Warna Solid Seleksi Kolom Spotlight Deep-Linking Ala Windows Explorer
- **Akar Masalah Warna Tidak Terlihat**:
  - Pada spesifikasi CSS Table Model (`display: table-row`), latar belakang yang dipasang pada tag baris `<tr>` berada di lapisan paling bawah dan tertutup oleh sel anak `<td>`.
  - Tingkat warna sebelumnya (`emerald-50/80` dan `blue-50/80`) memiliki tingkat kecerahan 98% yang nyaris putih murni sehingga di atas tabel putih perbedaannya hanya ~1.5% dan tidak tertangkap mata, sehingga hanya garis aksen kiri solid saja yang terlihat.
- **Implementasi Penetrasi Warna Kolom Menyeluruh (`scan-queue.blade.php` & `document-loan-index.blade.php`)**:
  - Memanfaatkan selector Tailwind `[&>td]` dengan override warna tegas agar seluruh sel kolom tabel langsung terisi warna seleksi solid:
    - **Antrean Scan**: `[&>td]:!bg-emerald-100 border-l-4 border-l-emerald-600 ring-1 ring-emerald-300 shadow-xs`.
    - **Meja Sirkulasi Tapersip**: `[&>td]:!bg-sky-100 border-l-4 border-l-sky-600 ring-1 ring-sky-300 shadow-xs` (warna biru langit seleksi khas Windows File Explorer).
  - Warna seleksi membentang jelas dan tegas dari kolom pertama hingga kolom aksi terakhir di semua jenis layar monitor.
  - Mempertahankan durasi fokus selama 5 detik dengan transisi memudar halus (*smooth 1-second fade out*) kembali ke baris tabel normal.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 74. Penyesuaian Spotlight Deep-Linking: Garis Abu-Abu Tipis & Latar Lembut Ala Windows Explorer
- **Akar Masalah Garis Gelap Bawah**:
  - Pada tabel HTML dengan arsitektur `border-collapse: collapse`, browser menggabungkan border tabel dan mengabaikan utility `ring`, sehingga menghasilkan garis gelap tebal pekat di bagian bawah baris yang merusak estetika antarmuka.
- **Implementasi Garis Abu-Abu Tipis & Latar Lembut Presisi (`document-loan-index.blade.php` & `scan-queue.blade.php`)**:
  - Mengganti border tebal/ring dengan properti outline presisi dan latar belakang abu-abu lembut:
    `:style="isTarget ? 'background-color: #f1f5f9; outline: 1.5px solid #cbd5e1; outline-offset: -1px; transition: all 0.7s ease;' : 'transition: all 0.7s ease;'"`
    beserta utility sel kolom: `[&>td]:!bg-[#f1f5f9]`.
  - **Hasil Visual**:
    - Membingkai baris yang dipilih dari seluruh sisi dengan garis abu-abu tipis rapi (`#cbd5e1` / Slate-300).
    - Seluruh kolom (dari awal hingga akhir baris) merata terisi warna abu-abu muda lembut (`#f1f5f9` / Slate-100), memberikan kesan seleksi yang tenang, profesional, dan persis seperti file yang sedang terseleksi di Windows File Explorer.
  - Mempertahankan auto-scroll halus ke tengah layar saat baris dituju dari notifikasi dan memudar perlahan setelah 5 detik kembali ke tampilan baris tabel normal.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 75. Redesain Popover Notifikasi Bespoke Enterprise (Card-less Rows, Tanpa Footerbar, & Scroll 10+ Data)
- **Penghapusan Total Footerbar (`tapersip-notification-listener.blade.php`)**:
  - Menghilangkan bilah navigasi bawah yang membuat panel tampak seperti widget sesak. Ujung bawah popover kini melengkung bersih dan rapi (`rounded-b-2xl`).
- **Pelegaan Kapasitas & Scrollbar Halus**:
  - Mengatur kontainer popover dengan lebar proporsional (`w-80 sm:w-[420px] md:w-[460px]`) dan ketinggian scrollable `max-h-[460px] overflow-y-auto overscroll-contain`.
  - Memasang scrollbar tipis modern (`[scrollbar-width:thin] [scrollbar-color:#cbd5e1_transparent]`) sehingga pengguna dapat men-scroll hingga 10–20 data tanpa tampilan yang kaku.
- **Tata Letak Baris Bersih & Bernapas (*Card-less Clean Rows*)**:
  - Menghilangkan badge bertumpuk dan lingkaran biru besar yang mengganggu visual.
  - **Indikator Unread Elegan**: Item belum dibaca ditandai dengan garis aksen vertikal kiri 3px (`border-l-[3px] border-l-[var(--color-primary)] bg-emerald-50/15`).
  - **Struktur Baris Teratur**:
    - Baris 1: Micro-badge kategori berikon ringkas (*Upload Scan*, *Pinjam Fisik*, *Batal Pinjam*) + waktu relatif monospace di kanan.
    - Baris 2: Nomor surat / kode tiket resmi dengan font monospace tebal dan jelas.
    - Baris 3: Nama pemohon, unit kerja, dan perihal dokumen ringkas dalam satu baris tenang tanpa ada pemenggalan canggung.
- **Penyelarasan Backend Query (`TapersipNotificationListener.php`)**:
  - Meningkatkan kuota kueri feed data scan naskah dan sirkulasi pinjam hingga masing-masing 15 item untuk menyuplai hingga 20 data unread dinamis.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 76. Penguncian Lebar Paten 380px, Eliminasi Elemen Redundan & Penataan Empty State Popover Notifikasi
- **Akar Masalah Kerusakan Layout Popover**:
  - Aset CSS produksi (`public/build/assets/app-*.css`) sebelumnya belum terkompilasi ulang dengan utilitas arbitrary Tailwind baru, sehingga browser merender kontainer dengan `width: auto`.
  - Saat ada notifikasi, dropdown menciut mengikuti teks sehingga teks *"Notifikasi"* dan pil counter *"1 baru"* bertabrakan (Gambar 1).
  - Saat notifikasi kosong (0 data), dropdown menciut drastis ke ~140px sehingga teks *"Notifikasi"* dan *"Semua selesai"* saling tumpuk dan gepeng (Gambar 2).
  - Elemen di dalam kartu notifikasi memiliki redundansi ganda (kotak hijau badge `[PINJAM FISIK]` bersanding dengan teks `Pengajuan Pinjam Fisik`).
- **Implementasi Solusi UI Presisi (`tapersip-notification-listener.blade.php`)**:
  - **Kunci Lebar Paten 380px**: Memasang lebar inline paten `style="width: 380px; max-width: calc(100vw - 2rem);"` pada kontainer popover. Ukuran dropdown kini **dijamin 100% konsisten (380px)** baik saat berisi data maupun saat kosong.
  - **Eliminasi Elemen Redundan**:
    - Menghapus badge kotak ganda yang memadati baris.
    - Menata 3 baris bersih:
      - *Baris 1*: Ikon aksi elegan + Judul tugas + Waktu relatif di kanan.
      - *Baris 2*: Kode tiket / nomor naskah monospace tebal + titik pemisah + nama pemohon & unit kerja.
      - *Baris 3*: Ringkasan perihal dokumen tenang satu baris.
  - **Penyempurnaan Header & Empty State**:
    - Header diberikan spasi lega (`gap-2`) sehingga judul, counter pil, dan tombol *"Tandai dibaca ✓"* tidak bertabrakan.
    - Tampilan saat kosong (*empty state*) memiliki ruang simetris, lega, dan nyaman di tengah kontainer selebar 380px.
- **Kompilasi Aset Frontend**:
  - Menjalankan `npm run build` untuk mengompilasi ulang seluruh bundle CSS produksi terbaru.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (61 *test cases*, 262 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 77. Penataan Alur Cetak Tiket Kearsipan: Eksklusif untuk Petugas Tapersip & Paperless Request bagi Staf
- **Penyederhanaan Alur Pengajuan Pinjam Staf (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Saat staf atau user biasa mengajukan pinjam dokumen fisik, sistem langsung menutup modal permohonan dan secara otomatis mengalihkan tampilan ke tab *"Pinjaman Saya"* (`$this->activeTab = 'my_loans'`).
  - Menghilangkan modal pop-up cetak tiket (`modal-loan-ticket`) bagi staf, digantikan dengan notifikasi sukses informatif: *"Permohonan pinjam berhasil diajukan dengan kode [TIKET-xxxx]. Petugas Tapersip sedang menyiapkan berkas fisik dan bukti peminjaman Anda."*
- **Penghapusan Aksi Cetak Tiket pada Tampilan Staf (`document-loan-index.blade.php`)**:
  - Menghilangkan tombol cetak ulang tiket (ikon printer) dari kolom aksi Tab 2 (*Pinjaman Saya*). Kolom aksi staf kini hanya menampilkan tombol *"Batal"* jika statusnya masih menunggu.
  - Memperbarui teks petunjuk status dari *"Bawa tiket ke Tapersip"* menjadi **`Menunggu Penyiapan Berkas`**, menciptakan pengalaman *paperless request* yang tenang dan tidak merepotkan staf.
- **Proteksi Otorisasi Cetak Tiket Eksklusif Tapersip**:
  - Pada method `DocumentLoanIndex::openTicketModal($loanId)`, ditambahkan validasi otorisasi `$this->canManageCirculation`. Staf biasa yang mencoba mengakses modal cetak tiket akan ditolak dengan error 403 (*Unauthorized*).
  - Aksi cetak tiket fisik hardcopy tetap tersedia eksklusif pada menu dropdown *"Pilihan Aksi"* di Tab 3 (*Persetujuan Tapersip*) bagi Petugas Tapersip yang bertugas mengambil berkas fisik dari lemari kearsipan dan menyematkan lembar kendali.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan pengujian `test_regular_user_cannot_open_ticket_modal_and_only_tapersip_can_print` (memvalidasi proteksi otorisasi 403 untuk staf biasa dan keberhasilan cetak untuk Tapersip).
  - Memperbarui pengujian `test_user_can_submit_loan_request_and_ticket_is_generated` untuk memvalidasi alur tanpa pembukaan modal tiket staf dan pengalihan ke tab Pinjaman Saya.
  - Seluruh rangkaian test suite proyek (62 *test cases*, 267 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 78. Lapisan Pengamanan Hak Akses Kerahasiaan (Sifat) & Batasan Divisi pada Katalog Pencarian Arsip
- **Latar Belakang Tata Kelola & Keamanan Informasi**:
  - Sebelum perbaikan, tabel katalog pencarian arsip (`DocumentLoanIndex`) menampilkan seluruh dokumen arsip kantor tanpa pembatasan hak akses, sehingga teks perihal dokumen sensitif (seperti sanksi SDM, gugatan hukum, atau audit keuangan) berpotensi terbaca oleh staf lintas divisi yang tidak berwenang.
- **Implementasi Model Akses Berlapis (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - **Pengecualian Penuh (Superadmin & Tapersip)**: Akun Superadmin dan Petugas Tapersip memiliki hak akses pengawasan penuh (*full visibility*) terhadap seluruh dokumen tanpa batasan.
  - **Dokumen Biasa**: Terbuka dan dapat dicari secara bebas oleh seluruh karyawan dari divisi mana pun untuk mendukung kelancaran operasional harian.
  - **Dokumen Rahasia**: Disembunyikan sepenuhnya (*hidden*) dari katalog pencarian staf biasa, kecuali jika staf tersebut adalah pembuat naskah (`created_by === user->id`) atau memiliki izin eksplisit `hasDocumentPermission('view', 'rahasia')`.
  - **Dokumen Penting**: Hanya ditampilkan kepada staf jika naskah berasal dari divisi/unit yang sama (`unit_id === user->getDivision()->id`), unit bawahan divisi, staf adalah pembuat naskah, atau jika divisi staf tercatat sebagai pihak penerima naskah dinas.
- **Proteksi Otorisasi Server-Side (`canAccessDocument`)**:
  - Memproteksi eksekusi method `openLoanModal($documentId)` dan `submitLoanRequest()` dengan validasi `canAccessDocument()`. Upaya peminjaman paksa terhadap naskah rahasia/penting divisi lain akan ditolak di tingkat server.
- **Penyelarasan Antarmuka Dropdown Filter (`document-loan-index.blade.php`)**:
  - Opsi filter "Rahasia" pada dropdown *Sifat Kerahasiaan* disembunyikan secara kondisional jika akun pengguna tidak berhak mengakses dokumen rahasia (`$canViewRahasia`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan 4 pengujian otomatis:
    1. `test_staff_cannot_see_confidential_rahasia_documents_in_catalog`
    2. `test_staff_cannot_see_important_penting_documents_from_other_divisions`
    3. `test_staff_can_see_ordinary_biasa_documents_across_all_divisions`
    4. `test_superadmin_can_see_all_documents_including_rahasia_and_other_divisions_penting`
  - Seluruh rangkaian test suite proyek (66 *test cases*, 278 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 79. Diferensiasi Batasan Hak Akses Dokumen Penting: Surat Keluar (Tingkat Divisi) vs Surat Khusus (Tingkat Departemen)
- **Aturan Tata Kelola Baru untuk Dokumen Bersifat Penting (`sifat = 'penting'`)**:
  - **Surat Keluar (`type = 'outgoing'`)**:
    - Dibuat pada level Divisi. Dokumen penting kategori ini hanya ditampilkan kepada staf jika naskah berasal dari **DIVISI yang sama** (`user->getDivision()->id === doc->unit->getDivision()->id`).
  - **Surat Khusus (`type = 'internal'`: Memo Internal, Surat Tugas, SK, dll.)**:
    - Dibuat untuk komunikasi antar unit kerja pada level Departemen dengan privasi lebih ketat.
    - Dokumen penting kategori ini **hanya ditampilkan jika berasal dari DEPARTEMEN yang sama persis** (`user->unit_id === doc->unit_id`).
    - Staf di departemen lain (meskipun bernaung di bawah divisi induk yang sama) **tidak dapat melihat** dokumen tersebut di katalog dan ditolak jika mencoba meminjam.
  - **Surat Masuk (`type = 'incoming'`)**: Ditampilkan jika naskah ditujukan ke departemen atau divisi akun staf.
  - **Pengecualian Tetap Berlaku**: Pihak unit penerima naskah dinas (`recipients`), pembuat naskah (`created_by === user->id`), serta Superadmin & Admin Tapersip selalu memiliki akses penuh.
- **Implementasi Komponen Backend (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Memperbarui method otorisasi server-side `canAccessDocument()` untuk membedakan aturan pengecekan unit per tipe korespondensi (`outgoing` vs `internal`).
  - Memperbarui sub-kueri katalog pada method `render()` sehingga kueri SQL otomatis memisahkan batasan divisi untuk Surat Keluar dan batasan departemen untuk Surat Khusus secara presisi.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan pengujian `test_staff_cannot_see_important_special_internal_documents_from_different_department_in_same_division`:
    - Memvalidasi bahwa staf di *Departemen Infrastruktur Jaringan* tidak dapat melihat Memo Internal penting milik *Departemen Pengembangan Aplikasi*, meskipun keduanya di bawah *Divisi TI* yang sama.
    - Memvalidasi bahwa staf sesama *Departemen Pengembangan Aplikasi* dapat melihat dan meminjam naskah tersebut.
  - Seluruh rangkaian test suite proyek (67 *test cases*, 283 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 80. Penyeragaman Format Nomor Urut 2 Digit pada Formulir Registrasi Surat Khusus
- **Latar Belakang**:
  - Format nomor urut pada *Surat Keluar* sebelumnya telah dibakukan menggunakan padding 2 digit (`01`, `02`, `03`, dst.).
  - Namun, pada generator nomor *Surat Khusus* (`app/Livewire/Registry/Internal/Create.php`), nomor urut masih menggunakan padding 3 digit (`001`, `002`), sehingga terjadi ketidaksinkronan format penomoran naskah dinas kantor.
- **Penyelarasan Kode Generator (`app/Livewire/Registry/Internal/Create.php`)**:
  - Mengubah fungsi padding pada baris 104 menjadi format 2 digit:
    `$sequenceNo = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);`
  - Hasilnya, penerbitan nomor Surat Khusus (Memo Internal, Surat Tugas, Surat Perintah, Surat Keputusan, dll.) kini seragam menggunakan format 2 digit baku: `01/MI/TI-DEV/2026`, `02/SPRIN/OPS-LOG/2026`, dst.
- **Verifikasi Pengujian Otomatis (`tests/Feature/SpecialLetterTest.php`)**:
  - Menambahkan pengujian `test_special_letter_numbering_uses_two_digit_format` yang memvalidasi bahwa nomor resmi Surat Khusus yang diterbitkan diawali format 2 digit angka (`^\d{2}\/`).
  - Seluruh rangkaian test suite proyek (68 *test cases*, 286 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 81. Peningkatan Skalabilitas Sistem: Penambahan Database Indexing & Eliminasi Kueri N+1 Peminjaman Berkas
- **Latar Belakang Skalabilitas & Performa**:
  - Mengantisipasi volume data besar hingga 1.000 naskah surat per akun (puluhan ribu record dokumen di database).
  - Mengeliminasi potensi *full table scan* dan kueri N+1 pada tabel katalog peminjaman berkas agar halaman tetap terbuka secepat kilat (< 0.3 detik).
- **Penambahan Database Indexing (`database/migrations/2026_10_20_000001_add_performance_indexes_to_documents_table.php`)**:
  - Menambahkan indeks tunggal dan komposit pada kolom-kolom tabel `documents` yang intensif digunakan dalam filter dan sorting:
    - `idx_documents_number` pada kolom `number`.
    - `idx_documents_document_date` pada kolom `document_date`.
    - `idx_documents_status` pada kolom `status`.
    - `idx_documents_sifat` pada kolom `sifat`.
    - Indeks komposit `idx_documents_type_status` pada `['type', 'status']` (sangat optimal untuk dashboard & antrean scan Tapersip).
    - Indeks komposit `idx_documents_status_date` pada `['status', 'document_date']` (sangat optimal untuk katalog pencarian arsip berkas).
- **Eliminasi Kueri N+1 pada Tabel Katalog (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Mengganti pemanggilan metode `$doc->isCurrentlyBorrowed()` dan `$doc->loans()->where(...)->exists()` yang sebelumnya memicu 20 kueri SQL tambahan per muat halaman menjadi evaluasi in-memory langsung dari relasi `$doc->activeLoan` yang sudah di-eager load:
    `$isBorrowed = $activeLoan && $activeLoan->status === 'borrowed';`
    `$isRequested = $activeLoan && $activeLoan->status === 'requested';`
  - Mengurangi beban database hingga 0 kueri tambahan per baris data.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (68 *test cases*, 286 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 82. Notifikasi Otomatis Email Persetujuan Peminjaman Berkas Fisik (Loan Approval Email)
- **Latar Belakang & Kebutuhan**:
  - Ketika staf mengajukan peminjaman berkas fisik di menu Peminjaman Berkas dan disetujui oleh Admin Tapersip (`approveLoan`), peminjam perlu mendapatkan notifikasi konfirmasi resmi lewat email untuk segera mengambil berkas fisik di unit Tapersip.
  - Tampilan email harus konsisten dan serasi dengan email pengarsipan dokumen (`document-archived.blade.php`), namun **tanpa footer sama sekali** sesuai instruksi.
- **Implementasi Notifikasi (`app/Notifications/DocumentLoanApprovedNotification.php`)**:
  - Dibuat kelas notifikasi dengan subjek: `Peminjaman Berkas Disetujui: {document_number}`.
  - Me-load relasi `['document.documentType', 'user.unit']` untuk memastikan performa efisien tanpa kueri N+1 saat rendering.
  - Menyediakan metadata lengkap: Nomor Berkas, Perihal, Jenis Dokumen, Tanggal Dokumen, Nama Peminjam, Unit Kerja, Tanggal Pinjam, Estimasi Jatuh Tempo, dan Lokasi Simpan Fisik (Rak / Box / Folder).
- **Desain Template Email Tanpa Footer (`resources/views/emails/loan-approved.blade.php`)**:
  - Mengadopsi visual layout modern khas e-arsip (header gradien elegan, kartu data rincian peminjaman berkas, badge status hijau disetujui, dan highlight lokasi fisik).
  - Menghapus komponen markup footer seutuhnya sesuai preferensi.
- **Pengiriman Asinkron Non-Blocking (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Memanfaatkan fungsi `defer(...)` Laravel 11 dengan *try...catch fail-safe* (`Log::warning`) pada saat tombol persetujuan ditekan di tabel Sirkulasi Peminjaman.
  - UI Tapersip tetap responsif instan (< 0.5 detik) tanpa terbebani latensi pengiriman SMTP.
- **Pengujian Otomatis (`tests/Feature/DocumentLoanNotificationTest.php`)**:
  - `test_borrower_receives_email_notification_when_loan_is_approved`: Memastikan peminjam menerima notifikasi saat permohonan disetujui.
  - `test_other_users_do_not_receive_loan_approval_notification`: Memastikan email tidak bocor ke akun pengguna lain.
  - `test_loan_approved_email_renders_correctly_without_footer`: Memverifikasi HTML email memuat seluruh rincian peminjaman dan tidak mengandung footer.
  - Seluruh rangkaian test suite proyek (71 *test cases*, 300 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 83. Floating In-App Visual Toast Persetujuan Peminjaman Berkas Fisik & Indeks Komposit Skalabilitas `document_loans`
- **Latar Belakang & Kebutuhan**:
  - Ketika Admin Tapersip menyetujui peminjaman berkas fisik (`approveLoan`), kontainer melayang **Floating In-App Visual Toast** pada sudut layar (`tapersip-notification-listener.blade.php`) harus langsung muncul memberikan konfirmasi instan: apakah status persetujuan berhasil dan apakah email notifikasi berhasil/gagal dikirim ke staf peminjam.
  - Memastikan performa kueri database pada tabel `document_loans` tetap optimal dan cepat seiring bertambahnya volume data sirkulasi peminjaman berkas kantor.
- **Penambahan Database Indexing (`database/migrations/2026_10_21_000001_add_performance_indexes_to_document_loans_table.php`)**:
  - `idx_document_loans_doc_status` pada `['document_id', 'status']` (sangat mempercepat kueri eager load relasi `activeLoan` di katalog pencarian berkas).
  - `idx_document_loans_user_status` pada `['user_id', 'status']` (mempercepat kueri tab 'Pinjaman Saya' dan filter status pinjaman akun pemohon).
  - `idx_document_loans_status_updated` pada `['status', 'updated_at']` (mempercepat kueri polling deteksi perubahan status real-time oleh listener).
- **Eksekusi Email Fail-Safe & Pemicu Toast (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Mengubah pengiriman email pada `approveLoan()` menjadi sinkron terproteksi blok `try { ... } catch (\Throwable $e) { ... }`:
    - **Kasus Sukses Email**: Memicu event `desktop-notify` dengan tipe `loan_approved_success`. Judul: *"Peminjaman Disetujui & Email Terkirim"*, memuat rincian berkas, kode tiket, nama staf, dan alamat email tujuan.
    - **Kasus Gagal Email**: Error koneksi SMTP ditangkap secara aman, status persetujuan berkas fisik di database **tetap sah dan aman** (tidak crash atau rollback), dan memicu event `desktop-notify` dengan tipe `loan_approved_mail_failed`. Judul: *"Peminjaman Disetujui (Email Gagal)"*, memuat instruksi kepada petugas untuk menghubungi pemohon secara manual.
    - **Kasus Pemohon Tanpa Email Valid**: Memicu tipe `loan_approved_mail_failed` dengan pesan informatif bahwa akun pemohon belum mendaftarkan alamat email.
  - Memancarkan event `loan-status-changed` untuk sinkronisasi otomatis seluruh tabel peminjaman.
- **Desain UI Toast Khusus Enterprise Non-Generic (`resources/views/livewire/components/tapersip-notification-listener.blade.php`)**:
  - Menghindari styling generic; menambahkan styling varian khusus yang elegan dan terintegrasi:
    - `loan_approved_success`: Border halus `border-emerald-200 ring-2 ring-emerald-500/10`, badge ikon hijau berkas centang terverifikasi (`M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z`), judul emerald gelap `text-emerald-900`, dan tombol aksi cepat *"Periksa Meja &rarr;"*.
    - `loan_approved_mail_failed`: Border amber peringatan `border-amber-200 ring-2 ring-amber-500/10`, badge ikon amber segitiga amplop peringatan, judul amber gelap `text-amber-900`, dan tombol aksi cepat *"Periksa Meja &rarr;"*.
  - Dilengkapi animasi *slide-in*, suara lonceng lembut Web Audio API, dan *auto-dismiss* 7 detik.
- **Penghapusan Total Footer Email (`resources/views/emails/loan-approved.blade.php`)**:
  - Membersihkan seluruh elemen markup `<tr><td class="footer">` dan kelas CSS `.footer` agar email murni bersih tanpa footer sesuai preferensi pengguna.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanNotificationTest.php`)**:
  - `test_approving_loan_dispatches_success_toast_notification`: Validasi dispatch event toast saat email sukses.
  - `test_approving_loan_handles_email_failure_gracefully_and_dispatches_warning_toast`: Validasi status tetap tersimpan dan toast peringatan dipicu saat exception SMTP terjadi.
  - `test_approving_loan_without_borrower_email_dispatches_mail_failed_toast`: Validasi toast penanganan pemohon tanpa email.
  - `test_loan_approved_email_renders_correctly_without_footer`: Memverifikasi HTML email ter-render bersih tanpa elemen footer.
  - Seluruh rangkaian test suite proyek (74 *test cases*, 308 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 84. Sinkronisasi Instan Badge Counter Notifikasi Tapersip via Event `loan-status-changed`
- **Latar Belakang & Kebutuhan**:
  - Saat petugas Tapersip menyetujui (`approveLoan`), menolak (`rejectLoan`), atau mengonfirmasi pengembalian berkas (`confirmReturn`), badge counter merah pada ikon lonceng notifikasi sebelumnya baru berkurang setelah interval polling 8 detik berjalan.
  - Untuk pengalaman pengguna enterprise yang responsif, badge counter harus langsung berkurang seketika (*zero-latency reactive update*) begitu tindakan selesai dieksekusi di tabel sirkulasi.
- **Implementasi Listener Reaktif (`app/Livewire/Components/TapersipNotificationListener.php`)**:
  - Menambahkan atribut listener Livewire 3:
    `#[On('loan-status-changed')] public function handleLoanStatusChanged(): void { $this->updatePendingCount(); }`
  - Setiap kali ada mutasi status permohonan pinjam berkas, komponen listener langsung memperbarui nilai `$this->pendingCount` secara otomatis tanpa menunggu siklus polling.
- **Penyempurnaan Aksi Sirkulasi (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Menambahkan pemancaran event `$this->dispatch('loan-status-changed')` pada:
    - `confirmReturn()`: Saat berkas fisik dikonfirmasi kembali ke loker.
    - `rejectLoan()`: Saat permohonan peminjaman ditolak oleh petugas.
- **Verifikasi Pengujian Otomatis (`tests/Feature/TapersipRealTimeNotificationTest.php`)**:
  - Menambahkan test case `test_listener_updates_pending_count_on_loan_status_changed_event` yang memvalidasi bahwa badge counter `pendingCount` langsung berkurang secara reaktif saat event `loan-status-changed` diterima tanpa pemanggilan method polling.
  - Seluruh rangkaian test suite proyek (75 *test cases*, 310 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 85. Modal Dialog Penolakan Berkas Fisik, Notifikasi Email Penolakan & Floating In-App Visual Toast
- **Latar Belakang & Kebutuhan**:
  - Sebelumnya, aksi tolak (`rejectLoan`) pada tabel Sirkulasi Tapersip hanya menggunakan konfirmasi browser bawaan (`wire:confirm`) tanpa formulir pengisian alasan penolakan. Akibatnya, pemohon tidak mengetahui mengapa permohonan pinjam berkas fisiknya ditolak.
  - Alur penolakan harus transparan, profesional, dan memberikan informasi lengkap: petugas menginput alasan penolakan via modal dialog resmi, pemohon menerima notifikasi email resmi dengan alasan penolakan (tanpa footer), dan layar Tapersip memunculkan konfirmasi Floating In-App Visual Toast rose/merah.
- **Implementasi Kelas Notifikasi Email (`app/Notifications/DocumentLoanRejectedNotification.php`)**:
  - Dibuat kelas notifikasi berbasis `toMail` dengan subjek: `Permohonan Pinjam Berkas Fisik {loan_code} Ditolak`.
  - Memuat relasi naskah, pemohon, unit kerja, nama petugas Tapersip, dan alasan spesifik penolakan (`reason`).
- **Template Email Penolakan Tanpa Footer (`resources/views/emails/loan-rejected.blade.php`)**:
  - Dirancang dengan layout enterprise elegan bernuansa merah rose berwibawa (`header bg-[#be123c]`, kartu rincian dokumen, dan highlight *reason-box* bergaris tebal merah).
  - Tanpa footer markup sama sekali sesuai preferensi pengguna.
- **Modal Dialog Penolakan & Kontrol Logika (`app/Livewire/Registry/DocumentLoanIndex.php` & `document-loan-index.blade.php`)**:
  - Menambahkan properti `$rejectNotes` dan `$selectedRejectLoan`.
  - Menambahkan method `openRejectModal($loanId)` yang membuka modal dialog `modal-loan-reject`.
  - Menambahkan method `confirmReject()` yang memvalidasi input alasan (minimal 3 karakter), mengupdate status permohonan ke `'rejected'`, menyimpan alasan di kolom `notes`, mencatat *Audit Log*, mengirimkan notifikasi email secara *fail-safe*, memancarkan event `loan-status-changed`, dan memicu *Floating Toast*.
- **Desain Floating In-App Visual Toast Khusus (`resources/views/livewire/components/tapersip-notification-listener.blade.php`)**:
  - Menambahkan varian tipe `loan_rejected_success`:
    - Border rose lembut `border-rose-200 ring-2 ring-rose-500/10`.
    - Badge ikon bulat tanda silang tegas (`M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z`).
    - Judul rose gelap `text-rose-900` (*"Permohonan Ditolak & Email Terkirim"*).
    - Tombol aksi *"Periksa Meja &rarr;"* dengan warna rose tegas (`bg-rose-600 hover:bg-rose-700`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanNotificationTest.php`)**:
  - `test_borrower_receives_rejection_email_and_toast_when_loan_is_rejected`: Memvalidasi pembukaan modal, pengisian alasan penolakan, mutasi database, pengiriman notifikasi email, dispatch toast event, dan sinkronisasi badge counter.
  - `test_loan_rejected_email_renders_correctly_without_footer`: Memverifikasi HTML email memuat rincian naskah, alasan penolakan, dan bersih tanpa elemen footer.
  - Seluruh rangkaian test suite proyek (77 *test cases*, 328 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 86. Notifikasi Otomatis Email Bukti Pengembalian Berkas Fisik (`DocumentLoanReturnedNotification`) & Floating In-App Visual Toast
- **Latar Belakang & Tata Kelola Dokumen**:
  - Saat staf peminjam mengembalikan berkas fisik ke loker Tapersip dan petugas mengonfirmasi pengembalian (`confirmReturn`), peminjam membutuhkan tanda terima digital resmi (*official digital return receipt*) yang membuktikan bahwa fisik naskah sudah aman kembali ke loker dan tanggungan pinjaman mereka telah lunas/selesai.
  - Tapersip juga memerlukan umpan balik visual instan di layar berupa *Floating In-App Visual Toast* biru terverifikasi yang mengonfirmasi status berkas kembali dan status pengiriman email.
- **Implementasi Notifikasi (`app/Notifications/DocumentLoanReturnedNotification.php`)**:
  - Dibuat kelas notifikasi dengan subjek: `Pengembalian Berkas Fisik {loan_code} Berhasil Dikonfirmasi`.
  - Me-load relasi `['document.documentType', 'user.unit']` untuk mencegah kueri N+1.
  - Memuat rincian naskah: Nomor Berkas, Perihal, Jenis Dokumen, Tanggal Pinjam, Tanggal Kembali Riil, Petugas Penerima Tapersip, Waktu Konfirmasi, dan Catatan Kondisi Berkas Fisik.
- **Template Email Bukti Pengembalian Tanpa Footer (`resources/views/emails/loan-returned.blade.php`)**:
  - Desain layout modern bernuansa biru keandalan (`header bg-[#2563eb]`, kartu rincian dokumen, dan highlight *notes-box* kondisi berkas).
  - Tanpa footer markup sama sekali sesuai preferensi pengguna.
- **Eksekusi Fail-Safe & Pemicu Toast (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Pada method `confirmReturn()`:
    - Melakukan update status ke `'returned'`, menyimpan tanggal pengembalian riil (`returned_date`), mencatat catatan kondisi ke `notes`, dan mencatat Audit Log.
    - Mengirimkan email tanda terima secara *fail-safe* menggunakan blok `try { ... } catch (\Throwable $e) { ... }`.
    - Memicu event `desktop-notify` dengan tipe `loan_returned_success` (atau `loan_approved_mail_failed` jika email terkendala).
    - Memancarkan event `loan-status-changed` untuk sinkronisasi tabel dan badge counter.
- **Desain Floating In-App Visual Toast Khusus (`resources/views/livewire/components/tapersip-notification-listener.blade.php`)**:
  - Menambahkan varian tipe `loan_returned_success`:
    - Border biru lembut `border-blue-200 ring-2 ring-blue-500/10`.
    - Badge ikon loker arsip terverifikasi (`M5 8h14M5 8a2 2...`).
    - Judul biru gelap `text-blue-900` (*"Berkas Kembali & Email Terkirim"*).
    - Tombol aksi *"Periksa Meja &rarr;"* dengan warna biru tegas (`bg-blue-600 hover:bg-blue-700`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanNotificationTest.php`)**:
  - `test_borrower_receives_return_confirmation_email_and_toast_when_loan_is_returned`: Memvalidasi pengembalian berkas, pembaruan database, pengiriman notifikasi email ke pemohon, dispatch event toast, dan sinkronisasi status.
  - `test_loan_returned_email_renders_correctly_without_footer`: Memverifikasi HTML email memuat seluruh rincian dan bersih tanpa elemen footer.
  - Seluruh rangkaian test suite proyek (79 *test cases*, 347 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 87. Peningkatan Visibilitas Catatan Penolakan & Kondisi Pengembalian Berkas pada Tabel Peminjaman
- **Latar Belakang & Kebutuhan**:
  - Pada tab "Pinjaman Saya", ketika permohonan pinjam berkas fisik ditolak atau telah dikembalikan, pemohon memerlukan visibilitas yang jelas dan kontras tentang alasan penolakan atau catatan kondisi berkas fisik yang dicatat oleh Tapersip.
  - Begitu pula pada tab "Sirkulasi Tapersip", petugas Tapersip membutuhkan indikasi ringkas langsung di bawah badge status tanpa harus membuka detail kueri catatan secara terpisah.
- **Penyempurnaan Tampilan Tab "Pinjaman Saya" (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Menambahkan kartu ringkasan visual khusus:
    - **Status Ditolak**: Menampilkan kartu bergaris lembut merah rose (`bg-rose-50 border-rose-200/80 text-rose-800 text-[11px]`) dengan label tebal `Alasan Penolakan:` beserta isi catatan penolakan dari Tapersip.
    - **Status Kembali**: Menampilkan kartu bergaris lembut hijau emerald (`bg-emerald-50 border-emerald-200/80 text-emerald-800 text-[11px]`) dengan label tebal `Kondisi Pengembalian:` beserta isi catatan kondisi berkas.
- **Penyempurnaan Tampilan Tab "Sirkulasi Tapersip" (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Di bawah badge status, menambahkan teks ringkas terpotong otomatis (*truncate*) dengan tooltip lengkap:
    - Teks rose untuk `Alasan: [Catatan]` pada baris berstatus Ditolak.
    - Teks emerald untuk `Kondisi: [Catatan]` pada baris berstatus Sudah Kembali.
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (79 *test cases*, 347 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 88. Floating In-App Visual Toast Pengarsipan Scan PDF & Sinkronisasi Instan Antrean Scan via Event `loan-status-changed`
- **Latar Belakang & Kebutuhan**:
  - Saat petugas Tapersip menyelesaikan unggah file scan berkas fisik dan mengarsipkannya (`saveFinalFile` di `ScanQueue`), petugas memerlukan konfirmasi visual instan pada layar (*Floating In-App Visual Toast*) yang memberitahukan bahwa dokumen berhasil diarsipkan dan email notifikasi resmi telah terkirim ke staf pembuat dokumen.
  - Selain itu, badge counter notifikasi lonceng Tapersip harus berkurang seketika (*zero latency*) tanpa harus menunggu interval polling 8 detik.
- **Implementasi Event Reaktif & Pemicu Toast (`app/Livewire/Registry/ScanQueue.php`)**:
  - Menambahkan pemancaran event `loan-status-changed` pada `saveFinalFile()` agar komponen `TapersipNotificationListener` langsung menghitung ulang `$pendingCount` antrean scan yang tersisa.
  - Menambahkan pemancaran event `desktop-notify` dengan tipe `scan_archived_success`:
    - Subjek: *"Dokumen Berhasil Diarsipkan"*
    - Pesan: *"Naskah {document_number} resmi diarsipkan. Email notifikasi telah dikirim ke {creator_name} ({creator_email})."*
    - URL: Langsung mengarah ke dokumen terkait di arsip keluar/khusus dengan parameter `highlight`.
- **Desain UI Toast Khusus Enterprise (`resources/views/livewire/components/tapersip-notification-listener.blade.php`)**:
  - Menambahkan varian tipe `scan_archived_success`:
    - Border emerald lembut `border-emerald-200 ring-2 ring-emerald-500/10`.
    - Badge ikon perisai arsip terverifikasi centang ganda (`M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z`).
    - Judul emerald gelap `text-emerald-900`.
    - Tombol aksi *"Buka Arsip &rarr;"* dengan warna `bg-[var(--color-primary)] hover:bg-emerald-800`.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentArchivedNotificationTest.php`)**:
  - Menambahkan test case `test_tapersip_upload_dispatches_archive_toast_and_loan_status_changed_event` yang memvalidasi bahwa event `desktop-notify` dengan tipe `scan_archived_success` dan event `loan-status-changed` terpancar sempurna serta status dokumen berubah ke `archived`.
  - Seluruh rangkaian test suite proyek (80 *test cases*, 352 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 89. Wadah Toast Notifikasi Banner Global Sistem (`notify` & Session Flash) di `app.blade.php`
- **Latar Belakang & Kebutuhan**:
  - Di seluruh modul aplikasi (Surat Masuk, Surat Keluar, Surat Khusus, Antrean Scan Tapersip, Peminjaman Berkas, dan Pemindahan Loker), terdapat lebih dari 30 pemanggilan `$this->dispatch('notify', ['message' => '...', 'type' => '...'])` dan `session()->flash('...')`.
  - Sebelumnya belum tersedia penampung visual mengambang global (*global in-app toast notification banner*) di layout utama `app.blade.php` untuk menangkap event `notify` tersebut. Akibatnya, pada beberapa halaman aksi notifikasi tidak memunculkan umpan balik visual instan.
- **Implementasi Wadah Notifikasi Global (`resources/views/components/layouts/app.blade.php`)**:
  - Ditempatkan wadah Alpine.js di sudut kanan atas layar (`fixed top-5 right-5 z-[9990] flex flex-col gap-2.5`).
  - Mendengarkan event JavaScript peramban `notify` secara global (`window.addEventListener('notify', ...)`).
  - Mendengarkan flash session Laravel (`session('success')`, `session('error')`, `session('warning')`, `session('info')`).
  - Dilengkapi fitur:
    - 4 palet warna enterprise: `success` (emerald), `error` (rose), `warning` (amber), `info` (blue).
    - Ikon khas sesuai tipe status.
    - Animasi halus *slide-in* dan *fade-out*.
    - Tombol tutup manual (*dismiss button*) dan *auto-dismiss* otomatis setelah 5 detik.
    - Menumpuk rapi di bawah layer toast real-time Tapersip (`z-[9999]`) tanpa terjadi tumpang tindih layout.
- **Verifikasi Pengujian Otomatis (`tests/Feature/GlobalNotificationToastTest.php`)**:
  - `test_global_layout_renders_toast_container_and_listens_to_notify_event`: Memvalidasi bahwa wadah HTML toast ter-render di layout dan mendengarkan event `notify`.
  - `test_session_flash_message_is_rendered_in_global_toast`: Memvalidasi bahwa flash session `success` berhasil diinjeksikan ke dalam array toast Alpine.js.
  - `test_session_error_message_is_rendered_in_global_toast`: Memvalidasi bahwa flash session `error` berhasil diinjeksikan ke dalam array toast Alpine.js.
  - Seluruh rangkaian test suite proyek (83 *test cases*, 362 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 90. Sinkronisasi Instan Event `loan-status-changed` pada Pengajuan dan Pembatalan Peminjaman Berkas
- **Latar Belakang & Kebutuhan**:
  - Pada alur peminjaman berkas fisik, saat staf mengajukan pinjam berkas (`submitLoanRequest`) atau membatalkan permohonan pinjam (`cancelLoanRequest`), badge counter merah notifikasi Tapersip sebelumnya baru terupdate saat interval polling 8 detik berjalan.
  - Untuk pengalaman pengguna multi-tab dan multi-user yang mulus dan bebas latensi, aksi pengajuan dan pembatalan peminjaman harus memancarkan event `loan-status-changed` secara real-time.
- **Penyempurnaan Aksi (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - Menambahkan pemancaran event `$this->dispatch('loan-status-changed')` pada:
    - `submitLoanRequest()`: Begitu tiket peminjaman dibuat di database dan modal permohonan ditutup.
    - `cancelLoanRequest()`: Begitu status permohonan diubah ke `'cancelled'` dan audit log tercatat.
  - Dengan pemancaran event ini, seluruh komponen terkait (tabel sirkulasi, tab pinjaman saya, dan ikon lonceng notifikasi Tapersip) langsung memperbarui status dan angka counter secara reaktif seketika.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Memperbarui pengujian `test_user_can_submit_loan_request_and_ticket_is_generated` dan `test_user_can_cancel_their_own_pending_loan_request` untuk memvalidasi bahwa event `loan-status-changed` terpancar tepat waktu saat pengajuan dan pembatalan dilakukan.
  - Seluruh rangkaian test suite proyek (83 *test cases*, 364 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 91. Filter Peminjaman Berkas Fisik Lewat Jatuh Tempo (`overdue`), Notifikasi Email Pengingat & Floating In-App Toast
- **Latar Belakang & Tata Kelola Kearsipan**:
  - Berkas fisik yang dipinjam staf berisiko terlambat dikembalikan melewati batas waktu (`expected_return_date`). Tapersip membutuhkan alat pemantau khusus untuk menyaring seluruh arsip yang sedang dipinjam namun telah lewat jatuh tempo, serta sarana untuk mengirimkan email pengingat (*Gentle Return Reminder Email*) resmi kepada pemohon.
  - Petugas Tapersip juga memerlukan umpan balik visual instan di layar berupa *Floating In-App Visual Toast* warna amber yang mengonfirmasi bahwa email pengingat telah terkirim.
- **Implementasi Filter Jatuh Tempo (`app/Livewire/Registry/DocumentLoanIndex.php` & `document-loan-index.blade.php`)**:
  - Menambahkan opsi filter baru pada dropdown status sirkulasi: `<option value="overdue">⚠️ Lewat Jatuh Tempo (Terlambat)</option>`.
  - Pada kueri sirkulasi method `render()`, menangani kondisi `$this->circulationStatus === 'overdue'` dengan menyaring `status = 'borrowed'` dan `expected_return_date < CURDATE()`.
- **Implementasi Notifikasi Email Pengingat (`app/Notifications/DocumentLoanReminderNotification.php`)**:
  - Dibuat kelas notifikasi berbasis `toMail` yang secara dinamis mendeteksi keterlambatan (`$isOverdue`):
    - Jika lewat jatuh tempo: Subjek `Pemberitahuan Keterlambatan Pengembalian Berkas Fisik {loan_code}`.
    - Jika sebelum jatuh tempo: Subjek `Pengingat Batas Pengembalian Berkas Fisik {loan_code}`.
  - Memuat relasi naskah, pemohon, unit kerja, tanggal pinjam, batas kembali (dengan penanda tegas jika terlambat), dan petunjuk pengembalian resmi.
- **Template Email Pengingat Tanpa Footer (`resources/views/emails/loan-reminder.blade.php`)**:
  - Desain layout enterprise bernuansa amber tegas (`header bg-[#d97706]` atau `#b45309`, kartu rincian dokumen, dan highlight *notice-box* petunjuk pengembalian).
  - Tanpa footer markup sama sekali sesuai preferensi pengguna.
- **Aksi Kirim Pengingat & Floating Toast (`app/Livewire/Registry/DocumentLoanIndex.php` & `document-loan-index.blade.php`)**:
  - Pada dropdown aksi baris sirkulasi berstatus `borrowed`, ditambahkan tombol `"Kirim Pengingat"`.
  - Method `sendReminderEmail($loanId)` mengeksekusi pengiriman notifikasi email secara *fail-safe*, mencatat Audit Log, memicu event `desktop-notify` dengan tipe `loan_reminder_success`, dan memunculkan toast banner.
- **Desain Floating In-App Visual Toast Khusus (`resources/views/livewire/components/tapersip-notification-listener.blade.php`)**:
  - Menambahkan varian tipe `loan_reminder_success`:
    - Border amber lembut `border-amber-200 ring-2 ring-amber-500/10`.
    - Badge ikon jam/pengingat (`M12 8v4l3 3m6-3a9 9...`).
    - Judul amber gelap `text-amber-900`.
    - Tombol aksi *"Periksa Meja &rarr;"* dengan warna `bg-amber-600 hover:bg-amber-700`.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanNotificationTest.php`)**:
  - `test_tapersip_can_filter_overdue_loans_in_circulation_table`: Memvalidasi keakuratan filter `overdue` menyaring hanya berkas yang lewat jatuh tempo.
  - `test_tapersip_can_send_reminder_email_for_borrowed_loan`: Memvalidasi pengiriman notifikasi email pengingat ke staf pemohon dan pemancaran event floating toast.
  - `test_loan_reminder_email_renders_correctly_without_footer`: Memverifikasi HTML email memuat seluruh rincian dan bersih tanpa elemen footer.
  - Seluruh rangkaian test suite proyek (86 *test cases*, 377 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 92. Perintah Konsol Artisan Pengingat Keterlambatan Pengembalian Berkas Fisik (`loans:send-overdue-reminders`) & Penjadwalan Harian
- **Latar Belakang & Kebutuhan**:
  - Untuk memitigasi risiko berkas fisik hilang atau tidak kunjung dikembalikan oleh staf peminjam dalam jangka panjang (*long-term overdue physical archives*), sistem memerlukan mekanisme otomasi berkala yang dapat berjalan otomatis di server setiap hari.
  - Perintah ini secara otomatis meninjau seluruh transaksi peminjaman berstatus `borrowed` yang telah melewati `expected_return_date`, lalu mengirimkan email pengingat resmi (*DocumentLoanReminderNotification*) kepada para peminjam.
- **Implementasi Perintah Konsol Artisan (`app/Console/Commands/SendOverdueLoanReminders.php`)**:
  - Signature perintah: `loans:send-overdue-reminders {--force : Abaikan rate-limiting 1 hari dan kirim ulang pengingat}`.
  - Fitur perlindungan *Anti-Spam Rate-Limiting*:
    - Menggunakan Cache kunci unik harian (`overdue_reminder_sent_{loan_id}_{Y-m-d}`) yang berlaku sampai akhir hari berjalan (`now()->endOfDay()`).
    - Memastikan setiap staf peminjam hanya menerima maksimal 1 email pengingat keterlambatan per hari untuk mencegah spam di kotak masuk mereka, kecuali jika opsi `--force` disertakan oleh administrator.
  - Mencatat *Audit Log* otomatis dengan detail transaksi dan tanggal jatuh tempo.
  - Memberikan ringkasan statistik terperinci di konsol terminal (*X pengingat terkirim, Y dilewati*).
- **Pendaftaran Penjadwalan Tugas Otomatis (`routes/console.php`)**:
  - Menjadwalkan eksekusi otomatis setiap hari pada pukul 08:00 WIB:
    `Schedule::command('loans:send-overdue-reminders')->dailyAt('08:00');`
- **Verifikasi Pengujian Otomatis (`tests/Feature/OverdueLoanReminderCommandTest.php`)**:
  - `test_command_sends_reminder_to_overdue_loans`: Memvalidasi bahwa perintah mengirimkan email pengingat hanya kepada peminjam yang berkasnya melewati batas tanggal dan mencatat audit log.
  - `test_command_rate_limiting_prevents_duplicate_reminders_on_same_day`: Memvalidasi bahwa eksekusi kedua di hari yang sama dilewati (*rate-limited*), dan opsi `--force` berhasil mengabaikan pembatasan tersebut.
  - `test_command_displays_no_loans_message_when_all_on_schedule`: Memvalidasi pesan informatif saat tidak ada pinjaman yang terlambat.
  - Seluruh rangkaian test suite proyek (89 *test cases*, 385 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 93. Kartu Statistik Bento Grid Peminjaman / Sirkulasi Berkas Fisik pada Dashboard Utama
- **Latar Belakang & Kebutuhan**:
  - Di halaman utama *Dashboard Kearsipan*, sebelumnya statistik naskah hanya mencakup Surat Masuk, Surat Keluar, Surat Khusus, Antrean Upload Scan, dan Retensi JRA.
  - Sirkulasi peminjaman berkas fisik kantor belum memiliki visualisasi metrik pada Bento Grid, sehingga petugas Tapersip maupun staf peminjam harus membuka menu peminjaman terlebih dahulu untuk mengetahui jumlah berkas fisik yang sedang aktif dipinjam atau berstatus terlambat.
- **Implementasi Logika Perhitungan & Hak Akses (`app/Livewire/Dashboard.php`)**:
  - Menghitung statistik peminjaman fisik secara efisien:
    - **Petugas Tapersip & Superadmin**: Menghitung seluruh transaksi berkas fisik yang sedang dipinjam (`status = 'borrowed'`) dan jumlah yang telah melewati jatuh tempo (`expected_return_date < CURDATE()`).
    - **Staf Pengguna**: Menghitung hanya naskah fisik yang sedang dipinjam oleh akun staf bersangkutan (`user_id = Auth::id()`).
  - Menambahkan pengecekan hak akses kartu via Role Matrix (`card_loans`, `loan_circulation`, atau `document_borrowing`).
  - Menyesuaikan kelas grid adaptif responsif (`xl:grid-cols-6`) agar tata letak 6 kartu bento tetap sejajar, proporsional, dan estetik.
- **Desain Kartu Bento Grid Enterprise (`resources/views/livewire/dashboard.blade.php`)**:
  - Mengadopsi palet warna indigo modern (`border border-slate-200/90 hover:border-indigo-300`, ikon dokumen arsip indigo, dan teks judul dinamis):
    - Untuk Tapersip/Superadmin: Berjudul *"Sirkulasi Berkas"*, mengarah ke tab Sirkulasi (`/borrowings?tab=circulation`).
    - Untuk Staf: Berjudul *"Pinjaman Saya"*, mengarah ke tab Pinjaman Saya (`/borrowings?tab=my_loans`).
    - Jika terdapat berkas yang melewati batas pengembalian, kartu menampilkan badge penanda tegas merah rose (`[X] Terlambat`).
- **Verifikasi Pengujian Otomatis (`tests/Feature/DashboardActivityLogTest.php`)**:
  - `test_superadmin_sees_loan_circulation_stats_card_on_dashboard`: Memvalidasi bahwa Superadmin/Tapersip melihat kartu Sirkulasi Berkas beserta badge keterlambatan di dashboard.
  - `test_staff_sees_personal_loan_stats_card_on_dashboard`: Memvalidasi bahwa staf biasa hanya melihat kartu Pinjaman Saya dengan angka personal mereka sendiri tanpa melihat data staf lain.
  - Seluruh rangkaian test suite proyek (91 *test cases*, 389 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 94. Sinkronisasi Teks Petunjuk Alur & Tombol Modal Pengajuan Pinjam Berkas Fisik
- **Latar Belakang & Kebutuhan**:
  - Setelah penyederhanaan alur di Bagian 77 (di mana pencetakan tiket bukti peminjaman dialihkan eksklusif ke petugas Tapersip yang bertugas menyiapkan berkas), teks petunjuk alur di dalam modal pengajuan (`modal-loan-request`) sebelumnya masih menyebutkan: *"Setelah pengajuan disimpan, sistem akan langsung menerbitkan Tiket Peminjaman Hardcopy. Cetak tiket tersebut dan bawa ke meja Tapersip..."* serta label tombol submit masih bertuliskan *"Terbitkan Tiket Pinjam"*.
  - Teks tersebut perlu disinkronkan agar selaras dengan alur kerja riil yang sudah disederhanakan: staf pemohon mengajukan pinjaman, Tapersip menerima notifikasi untuk menyiapkan berkas dan mencetak tiket, lalu staf menerima email konfirmasi resmi saat berkas siap diambil.
- **Penyempurnaan UI Modal Dialog (`resources/views/livewire/registry/document-loan-index.blade.php`)**:
  - Memperbarui teks petunjuk alur: *"Setelah permohonan dikirim, petugas Tapersip akan menerima notifikasi untuk menyiapkan berkas fisik dan mencetak bukti peminjaman. Anda akan menerima notifikasi email konfirmasi saat berkas siap diambil."*
  - Mengubah label tombol submit formulir dari *"Terbitkan Tiket Pinjam"* menjadi *"Kirim Permohonan Pinjam"*.
  - Merapikan sintaks CSS border pada kotak lokasi dokumen arsip di tiket cetak (`border-2 border-slate-800 border-dashed`).
- **Verifikasi Pengujian Otomatis**:
  - Seluruh rangkaian test suite proyek (91 *test cases*, 389 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 95. Integrasi Audit Log & Floating In-App Toast Notifikasi pada Modul Usul Pemusnahan Arsip (`DestructionIndex`)
- **Latar Belakang & Kebutuhan Kepatuhan Kearsipan (JRA / ANRI)**:
  - Pemusnahan arsip inaktif yang telah habis masa retensinya merupakan aktivitas krusial dalam tata kelola kearsipan resmi.
  - Sebelumnya, aksi pengajuan usul (`submitProposal`), persetujuan (`approveProposal`), penolakan (`rejectProposal`), dan eksekusi pemusnahan (`executeDestruction`) di komponen Livewire `DestructionIndex` hanya memicu `session()->flash('...')` tanpa mencatat riwayat audit ke tabel `audit_logs`. Karena pemanggilan aksi Livewire berlangsung secara dinamis (AJAX), pesan flash sesi tidak memicu banner toast mengambang secara langsung.
- **Implementasi Pencatatan Audit Trail (`app/Livewire/Archive/DestructionIndex.php`)**:
  - `submitProposal()`: Mencatat `AuditLog::record('Pengajuan Usulan Pemusnahan Arsip: [Nomor Surat]', metadata: ['reason', 'proposer_id'])`.
  - `approveProposal()`: Mencatat `AuditLog::record('Persetujuan Usulan Pemusnahan Arsip: [Nomor Surat]', metadata: ['record_id', 'approver_id'])`.
  - `rejectProposal()`: Mencatat `AuditLog::record('Penolakan Usulan Pemusnahan Arsip: [Nomor Surat]', metadata: ['record_id', 'approver_id'])`.
  - `executeDestruction()`: Mencatat `AuditLog::record('Eksekusi Pemusnahan Arsip & Upload Berita Acara: [Nomor Surat]', metadata: ['record_id', 'ba_file_path', 'executor_id', 'executed_at'])`.
- **Integrasi Umpan Balik Visual Instan (Floating Toast)**:
  - Mengirimkan event `$this->dispatch('notify', ['message' => '...', 'type' => '...'])` pada setiap aksi, sehingga kontainer banner toast global di `app.blade.php` langsung memunculkan kartu notifikasi elegan di sudut kanan atas layar peramban.
  - Menambahkan eager loading `['documentType', 'classification', 'unit', 'folder']` pada kueri `expiredDocuments` dan `['document.documentType', 'proposer', 'approver', 'executor']` pada kueri `proposals` untuk meniadakan potensi kueri N+1.
- **Verifikasi Pengujian Otomatis (`tests/Feature/ArchiveDestructionTest.php`)**:
  - `test_user_can_propose_document_destruction_with_audit_log_and_notify_toast`: Memvalidasi pengajuan usul pemusnahan arsip dan verifikasi pencatatan audit log serta event toast.
  - `test_authorized_user_can_approve_proposal_with_audit_log_and_notify_toast`: Memvalidasi persetujuan usul pemusnahan arsip.
  - `test_authorized_user_can_reject_proposal_with_audit_log_and_notify_toast`: Memvalidasi penolakan usul pemusnahan arsip.
  - `test_authorized_user_can_execute_destruction_with_ba_file_and_audit_log`: Memvalidasi unggah file Berita Acara (BA), mutasi status dokumen ke `disposed`, dan pencatatan audit log eksekusi.
  - Seluruh rangkaian test suite proyek (95 *test cases*, 413 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 96. Standardisasi Tampilan Paginasi Kustom pada Modul Usul Pemusnahan Arsip (`DestructionIndex`)
- **Latar Belakang & Kebutuhan**:
  - Pada modul Usul Pemusnahan Arsip (`resources/views/livewire/archive/destruction-index.blade.php`), tautan paginasi pada tab Dokumen Habis Retensi dan tab Usulan & Eksekusi sebelumnya masih memanggil default bawaan Laravel `links()` tanpa menentukan tema kustom.
  - Untuk menjaga konsistensi visual di seluruh aplikasi agar seragam dengan modul Surat Masuk, Surat Keluar, Surat Khusus, Antrean Scan Tapersip, dan Peminjaman Berkas, paginasi distandardisasi menggunakan view kustom `vendor.pagination.custom`.
- **Penyempurnaan Tampilan Blade (`resources/views/livewire/archive/destruction-index.blade.php`)**:
  - Mengubah tautan paginasi tab berkas habis retensi menjadi: `{{ $expiredDocuments->links('vendor.pagination.custom') }}`.
  - Mengubah tautan paginasi tab usulan & eksekusi menjadi: `{{ $proposals->links('vendor.pagination.custom') }}`.
  - Memberikan visualisasi navigasi halaman berbahasa Indonesia (*"Menampilkan [X] – [Y] dari [Z] data"*, tombol *Prev/Next* yang rapi, dan indikator nomor halaman yang elegan).
- **Verifikasi Pengujian Otomatis (`tests/Feature/ArchiveDestructionTest.php`)**:
  - Menambahkan pengujian `test_destruction_index_renders_custom_pagination_view` yang membuat 12 berkas habis masa retensi (>10 data per halaman) dan memvalidasi bahwa tampilan paginasi kustom ter-render sempurna.
  - Seluruh rangkaian test suite proyek (96 *test cases*, 417 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 97. Penyelarasan Menyeluruh (*100% Full Unification*) Paginasi Kustom di Seluruh Modul Pengaturan & Master Data
- **Latar Belakang & Kebutuhan Konsistensi UI**:
  - Setelah standardisasi paginasi kustom pada modul-modul registrasi berkas dan pemusnahan arsip, ditemukan beberapa view pengaturan yang masih memanggil default bawaan Laravel `links()` tanpa menentukan view kustom:
    - Manajemen Pengguna (`user-index.blade.php`)
    - Manajemen Role & Izin Akses (`role-index.blade.php`)
    - Master Data Unit Kerja (`organization-index.blade.php` - tab Unit & tab Jabatan)
    - Master Data Klasifikasi & Jenis Dokumen (`archive-classification-index.blade.php` - tab Klasifikasi & tab Jenis Dokumen)
    - Audit Trail & Log Aktivitas Sistem (`audit-trail-index.blade.php`)
  - Untuk menciptakan pengalaman visual enterprise yang 100% konsisten, seragam, dan profesional di setiap sudut antarmuka sistem e-arsip, seluruh tautan paginasi pada modul-modul tersebut distandardisasi menggunakan view kustom `vendor.pagination.custom`.
- **Implementasi Penyelarasan**:
  - `user-index.blade.php`: `{{ $users->links('vendor.pagination.custom') }}`
  - `role-index.blade.php`: `{{ $roles->links('vendor.pagination.custom') }}`
  - `organization-index.blade.php`: `{{ $units->links('vendor.pagination.custom') }}` dan `{{ $positions->links('vendor.pagination.custom') }}`
  - `archive-classification-index.blade.php`: `{{ $classifications->links('vendor.pagination.custom') }}` dan `{{ $documentTypes->links('vendor.pagination.custom') }}`
  - `audit-trail-index.blade.php`: `{{ $logs->links('vendor.pagination.custom') }}`
- **Hasil & Verifikasi**:
  - Seluruh modul pada sistem e-arsip kini 100% menggunakan view paginasi kustom yang rapi, responsif, berbahasa Indonesia, dan seragam.
  - Seluruh rangkaian test suite proyek (96 *test cases*, 417 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 98. Pengamanan Integritas Akun/Role, Pencatatan Audit Log & Notifikasi Toast pada Manajemen Pengguna & Role
- **Latar Belakang & Kebutuhan Keamanan Sistem**:
  - Pada menu Pengaturan Pengguna (`UserIndex`) dan Role (`RoleIndex` & `RoleEditor`), aksi simpan dan hapus sebelumnya tidak memberikan umpan balik visual instan (toast notification) dan tidak mencatat riwayat ke tabel `audit_logs`.
  - Selain itu, belum ada pengamanan protektif (*safety guards*) untuk mencegah administrator menghapus akunnya sendiri yang sedang aktif atau menghapus akun master Superadmin, serta belum ada validasi pencegahan penghapusan role yang masih terikat pada pengguna aktif.
- **Implementasi Pengamanan & Audit Trail (`app/Livewire/Settings/UserIndex.php`)**:
  - `save()`:
    - Membedakan aksi pembuatan pengguna baru vs pembaruan data pengguna.
    - Mencatat `AuditLog::record('Registrasi Pengguna Baru: [Nama]' / 'Pembaruan Data Pengguna: [Nama]', metadata: [...])`.
    - Mengirimkan event `$this->dispatch('notify', ['message' => '...', 'type' => 'success'])` untuk memicu toast visual instan.
  - `delete($id)`:
    - *Safety Guard 1*: Mencegah user menghapus akunnya sendiri (`if ($id == Auth::id())`), memicu toast error.
    - *Safety Guard 2*: Mencegah penghapusan akun Superadmin utama (`if ($user->username === 'superadmin')`), memicu toast error.
    - Menghapus akun secara *soft delete*, mencatat `AuditLog::record("Penghapusan Pengguna: [Nama]", ...)`, dan memicu toast sukses.
- **Implementasi Pengamanan & Audit Trail (`app/Livewire/Settings/RoleIndex.php` & `RoleEditor.php`)**:
  - `deleteRole($id)`:
    - *Safety Guard 1*: Mencegah penghapusan role Superadmin (`if ($role->slug === 'superadmin')`), memicu toast error.
    - *Safety Guard 2*: Mencegah penghapusan role yang masih digunakan oleh pengguna aktif (`if ($role->users_count > 0)`), memicu toast error informatif memuat jumlah pengguna terkait.
    - Menghapus role secara *soft delete*, mencatat `AuditLog::record("Penghapusan Role: [Nama]", ...)`, dan memicu toast sukses.
  - `save()` pada `RoleEditor`:
    - Mencatat `AuditLog::record('Pembuatan Role Baru: [Nama]' / 'Pembaruan Konfigurasi Role: [Nama]', metadata: [...])`.
    - Mengatur session flash `success` yang otomatis ditangkap oleh global toast banner di `app.blade.php`.
- **Verifikasi Pengujian Otomatis (`tests/Feature/UserAndRoleManagementTest.php`)**:
  - `test_admin_can_create_user_with_audit_log_and_notify_event`: Memvalidasi pendaftaran user dan pencatatan audit log.
  - `test_admin_cannot_delete_own_account`: Memvalidasi penolakan penghapusan akun sendiri.
  - `test_admin_cannot_delete_superadmin_user`: Memvalidasi penolakan penghapusan superadmin.
  - `test_admin_can_delete_regular_user_with_audit_log_and_notify`: Memvalidasi soft delete user dan audit log.
  - `test_admin_cannot_delete_role_in_use`: Memvalidasi proteksi role yang masih memiliki user.
  - `test_admin_can_delete_unused_role_with_audit_log_and_notify`: Memvalidasi penghapusan role tak terpakai.
  - `test_admin_can_save_role_in_editor_with_audit_log_and_flash_session`: Memvalidasi penyimpanan konfigurasi role via RoleEditor.
  - Seluruh rangkaian test suite proyek (103 *test cases*, 439 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 99. Pengamanan Integritas Data Master, Relasi Model Eloquent & Pencatatan Audit Log pada Unit Kerja, Jabatan, Klasifikasi & Jenis Dokumen
- **Latar Belakang & Kebutuhan Proteksi Data**:
  - Pada master data organisasi (`OrganizationIndex`) dan klasifikasi kearsipan (`ArchiveClassificationIndex`), aksi simpan dan hapus sebelumnya belum mencatat riwayat ke tabel `audit_logs` dan belum memicu notifikasi toast global.
  - Yang paling krusial, aksi hapus belum memiliki pengaman integritas data (*relational safety guards*), sehingga unit kerja yang masih memiliki staf/departemen bawahan/dokumen arsip, atau klasifikasi/jenis dokumen yang masih digunakan pada naskah arsip berisiko terhapus dan merusak integritas data sistem.
- **Implementasi Relasi Eloquent Antar-Model (`Unit.php`, `Classification.php`, `DocumentType.php`)**:
  - Menambahkan relasi `documents(): HasMany` pada model `Unit` (`hasMany(Document::class)`).
  - Menambahkan relasi `documents(): HasMany` pada model `Classification` (`hasMany(Document::class)`).
  - Menambahkan relasi `documents(): HasMany` pada model `DocumentType` (`hasMany(Document::class)`).
- **Pengamanan & Audit Trail Master Data Organisasi (`app/Livewire/Settings/OrganizationIndex.php`)**:
  - `saveUnit()`: Mencatat `AuditLog::record('Pembuatan Unit Kerja: ...' / 'Pembaruan Unit Kerja: ...')` dan memicu toast sukses.
  - `deleteUnit($id)`:
    - *Safety Guard 1*: Mencegah penghapusan unit kerja yang masih memiliki staf aktif (`users_count > 0`).
    - *Safety Guard 2*: Mencegah penghapusan divisi yang masih membawahi departemen aktif (`children_count > 0`).
    - *Safety Guard 3*: Mencegah penghapusan unit kerja yang masih terkait dengan arsip naskah (`documents_count > 0`).
    - Melakukan soft delete, mencatat Audit Log, dan memicu toast sukses jika unit steril tanpa relasi aktif.
  - `savePosition()`: Mencatat Audit Log dan memicu toast sukses.
  - `deletePosition($id)`: Mencegah penghapusan jabatan yang masih digunakan staf aktif (`users_count > 0`), mencatat Audit Log, dan memicu toast sukses.
- **Pengamanan & Audit Trail Klasifikasi & Jenis Dokumen (`app/Livewire/Settings/ArchiveClassificationIndex.php`)**:
  - `saveClassification()`: Mencatat Audit Log pembuatan/pembaruan klasifikasi dan memicu toast sukses.
  - `deleteClassification($id)`:
    - *Safety Guard 1*: Mencegah penghapusan klasifikasi yang membawahi sub-klasifikasi (`children_count > 0`).
    - *Safety Guard 2*: Mencegah penghapusan klasifikasi yang masih digunakan dokumen arsip (`documents_count > 0`).
    - Melakukan soft delete, mencatat Audit Log, dan memicu toast sukses jika klasifikasi bersih.
  - `saveDocType()`: Mencatat Audit Log pembuatan/pembaruan jenis dokumen dan memicu toast sukses.
  - `deleteDocType($id)`: Mencegah penghapusan jenis dokumen yang masih digunakan oleh arsip naskah (`documents_count > 0`), mencatat Audit Log, dan memicu toast sukses.
- **Verifikasi Pengujian Otomatis (`tests/Feature/MasterDataManagementTest.php`)**:
  - 9 test cases komprehensif menguji pembuatan unit, pencegahan hapus divisi ber-anak, pencegahan hapus unit ber-user, soft delete unit aman, pembuatan & penghapusan jabatan, pencegahan hapus klasifikasi ber-sub, penghapusan klasifikasi aman, pencegahan hapus jenis dokumen in-use, dan penghapusan jenis dokumen aman.
  - Seluruh rangkaian test suite proyek (112 *test cases*, 467 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 100. Penyelarasan Hak Akses Hierarki Kearsipan: Isolasi Horizontal Departemen & Pewarisan Vertikal ke Atas (Upward Inheritance) pada Dokumen Penting
- **Latar Belakang & Tata Kelola (*Enterprise Governance*)**:
  - Berdasarkan prinsip *Need-to-Know* dan tata kelola kearsipan perusahaan, naskah dinas berderajat **Penting** (`sifat = 'penting'`) baik pada **Surat Keluar** maupun **Surat Khusus** membutuhkan isolasi kerahasiaan antar-departemen bawahan yang berada di bawah divisi yang sama.
  - *Contoh Kasus*: Naskah penting yang dibuat oleh akun staf di *Departemen MGTTI* (di bawah *Divisi Operasional*) tidak boleh dapat dilihat atau dipinjam oleh staf dari *Departemen Logistik*, meskipun sama-sama bernaung di bawah *Divisi Operasional*.
  - Namun, secara struktural (*chain of command*), akun pengguna yang melekat langsung pada unit induk bertipe **Divisi** (*Divisi Operasional*, seperti Kepala Divisi atau Sekretariat Divisi) wajib memiliki hak pemantauan penuh (*Upward Inheritance*) terhadap seluruh naskah yang diterbitkan oleh departemen-departemen di bawah naungannya.
- **Implementasi Komponen Backend (`app/Livewire/Registry/DocumentLoanIndex.php`)**:
  - **Resolusi Unit Naskah**:
    - Departemen asal naskah (`docDepartmentId`) ditentukan dari `unit_id` naskah (jika departemen) atau dari unit kerja pembuat naskah (`createdBy.unit`).
    - Divisi asal naskah (`docDivisionId`) ditentukan dari divisi dokumen atau divisi pembuat naskah (`unit.parent_id` / `getDivision()`).
  - **Method `canAccessDocument()`**:
    - *Aturan 1 (Pewarisan Vertikal)*: Akun level Divisi (`user->unit->type === 'division'`) dapat mengakses seluruh dokumen penting yang berasal dari divisinya dan departemen-departemen di bawahnya (`userDivisionId === docDivisionId`).
    - *Aturan 2 (Isolasi Horizontal)*: Akun level Departemen hanya dapat mengakses naskah penting jika berasal dari departemen yang sama persis (`user->unit_id === docDepartmentId`). Staf dari departemen lain di divisi yang sama otomatis ditolak.
    - *Aturan 3*: Pihak penerima (`recipients`), pembuat naskah (`created_by`), pemegang izin role `view penting`, serta Superadmin & Admin Tapersip tetap memiliki akses sah.
  - **Penyelarasan Kueri Katalog `render()`**:
    - Eager loading diperkaya dengan `['folder', 'unit.parent', 'documentType', 'activeLoan.user', 'createdBy.unit']`.
    - Subkueri SQL untuk dokumen penting diselaraskan secara presisi sehingga tabel katalog di tab 1 secara otomatis menyaring naskah penting sesuai aturan hierarki di atas.
- **Verifikasi Pengujian Otomatis (`tests/Feature/DocumentLoanTest.php`)**:
  - Menambahkan pengujian `test_hierarchical_department_isolation_and_division_upward_inheritance_for_important_documents`:
    - Memvalidasi bahwa staf *Departemen Logistik* tidak dapat melihat Surat Keluar maupun Surat Khusus penting buatan staf *Departemen MGTTI* (meskipun keduanya di bawah *Divisi Operasional* yang sama).
    - Memvalidasi bahwa upaya staf *Departemen Logistik* meminjam naskah tersebut diblokir oleh sistem (`403` / toast error).
    - Memvalidasi bahwa rekan sesama *Departemen MGTTI* dapat melihat dan meminjam naskah tersebut.
    - Memvalidasi bahwa akun pimpinan di level induk *Divisi Operasional* dapat melihat dan meminjam seluruh naskah dari departemen di bawahnya.
  - Seluruh rangkaian test suite proyek (113 *test cases*, 481 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.

### 101. Perataan Sumbu Vertikal Toast Notifikasi Global & Smart Page Resolver Spotlight Paginasi Antrean Scan & Sirkulasi
- **Latar Belakang & Masalah**:
  1. *Perataan Vertikal Toast Notifikasi*:
     - Teks pesan notifikasi (`Status koneksi NAS telah diperbarui.`) terlihat kurang presisi secara vertikal terhadap ikon informasi lingkaran `(i)`. Hal ini disebabkan kontainer menggunakan perataan atas (`items-start`), perbedaan tinggi baris teks dengan tinggi kotak ikon (`h-7 w-7`), serta adanya kelas margin asimetris (`mt-0.5`).
  2. *Anomali Spotlight pada Halaman Paginasi ke-2 ke atas*:
     - Pada halaman Antrean Scan Tapersip (`ScanQueue`) dan Sirkulasi Peminjaman (`DocumentLoanIndex`), ketika data target notifikasi berada pada urutan ke-11 ke atas (halaman 2 atau seterusnya), URL `?highlight=doc-XX` atau `?highlight=loan-XX` hanya membuka Halaman 1. Akibatnya, elemen baris target tidak ada di DOM, sehingga efek *smooth scroll* dan *spotlight selection* gagal menyala dan pengguna mengira data tidak ditemukan.
- **Penyempurnaan Perataan Toast Notifikasi (`resources/views/components/layouts/app.blade.php`)**:
  - Mengubah perataan flex kontainer utama dari `items-start` menjadi `items-center`.
  - Menghilangkan margin `mt-0.5` pada badge ikon dan tombol close agar seluruh elemen sejajar presisi di sumbu horizontal tengah (*100% vertically centered*).
  - Mengubah sudut lengkung badge ikon menjadi lingkaran penuh (`rounded-full`) dan menyelaraskan baris teks dengan `leading-normal`.
- **Implementasi Smart Page Resolver Backend**:
  - **`app/Livewire/Registry/ScanQueue.php`**:
    - Pada method `mount()`, sistem mengekstrak parameter `highlight=doc-{id}`.
    - Menghitung posisi antrean dokumen target secara instan (< 1ms) berdasarkan urutan antrean FIFO (`created_at asc, id asc`):
      $$\text{Target Page} = \lceil \text{Posisi} / 10 \rceil$$
    - Jika target berada di halaman 2 atau seterusnya, Livewire langsung memanggil `$this->setPage($targetPage)` saat *lifecycle* `mount()`. Server menyajikan halaman terkait secara otomatis, elemen baris `#doc-row-{id}` langsung ada di DOM, dan skrip Alpine.js mengeksekusi *smooth scroll* serta spotlight seleksi abu-abu lembut selama 5 detik.
    - Menyelaraskan kueri render dengan `orderBy('created_at', 'asc')->orderBy('id', 'asc')` untuk urutan yang deterministik.
  - **`app/Livewire/Registry/DocumentLoanIndex.php`**:
    - Menerapkan Smart Page Resolver pada method `mount()` untuk parameter `highlight=loan-{id}` pada tab Sirkulasi Tapersip (`circulationPage`).
    - Menghitung posisi baris sirkulasi target berdasarkan urutan LIFO (`latest('created_at')->latest('id')`) dan otomatis mengeksekusi `$this->setPage($targetPage, 'circulationPage')`.
- **Verifikasi Pengujian Otomatis**:
  - `tests/Feature/NasStorageTest.php`: Menambahkan test case `test_scan_queue_smart_page_resolver_navigates_to_correct_page_for_highlight_parameter` yang membuat 15 dokumen antrean dan memvalidasi bahwa membuka dokumen urutan ke-13 langsung menyajikan halaman 2 dan merender baris `#doc-row-{id}`.
  - `tests/Feature/DocumentLoanTest.php`: Menambahkan test case `test_circulation_smart_page_resolver_navigates_to_correct_page_for_highlight_parameter` yang membuat 15 data sirkulasi dan memvalidasi bahwa membuka tiket pinjaman urutan ke-15 langsung menyajikan halaman 2 tab sirkulasi dan merender baris `#loan-row-{id}`.
  - Seluruh rangkaian test suite proyek (115 *test cases*, 489 *assertions*) terkonfirmasi **lulus 100% (*PASS*)**.


























































