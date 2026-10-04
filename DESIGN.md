---
name: E-Arsip Enterprise Design System
description: Work-management and document governance system designed for high-density enterprise operations, built for Laravel + Livewire + Tailwind CSS.
colors:
  primary: "#0B2E28"
  primary-hover: "#134E44"
  primary-light: "#F0FDF4"
  accent: "#10B981"
  background: "#F4F6F5"
  surface: "#FFFFFF"
  surface-sidebar: "#0B2E28"
  surface-sidebar-hover: "rgba(255, 255, 255, 0.08)"
  text-primary: "#0F172A"
  text-secondary: "#64748B"
  text-inverse: "#FFFFFF"
  text-sidebar-muted: "#94A3B8"
  border: "#E2E8F0"
  border-subtle: "#F1F5F9"
  border-sidebar: "rgba(255, 255, 255, 0.1)"
  status:
    draft: { bg: "#F8FAFC", text: "#475569", border: "#CBD5E1" }
    pending: { bg: "#EFF6FF", text: "#1D4ED8", border: "#BFDBFE" }
    review: { bg: "#FFFBEB", text: "#B45309", border: "#FDE68A" }
    approved: { bg: "#ECFDF5", text: "#047857", border: "#A7F3D0" }
    rejected: { bg: "#FEF2F2", text: "#DC2626", border: "#FECACA" }
    disposed: { bg: "#FAF5FF", text: "#7E22CE", border: "#E9D5FF" }
typography:
  fontFamily: "'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif"
  h1:
    fontSize: "1.5rem"
    fontWeight: "700"
    lineHeight: "1.25"
    letterSpacing: "-0.015em"
  h2:
    fontSize: "1.125rem"
    fontWeight: "600"
    lineHeight: "1.3"
  h3:
    fontSize: "0.9375rem"
    fontWeight: "600"
    lineHeight: "1.4"
  body-md:
    fontSize: "0.875rem"
    fontWeight: "400"
    lineHeight: "1.5"
  body-sm:
    fontSize: "0.8125rem"
    fontWeight: "400"
    lineHeight: "1.4"
  label:
    fontSize: "0.75rem"
    fontWeight: "600"
    letterSpacing: "0.025em"
    textTransform: "uppercase"
  code-doc-number:
    fontFamily: "'JetBrains Mono', monospace"
    fontSize: "0.8125rem"
    fontWeight: "500"
rounded:
  xs: "4px"
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "16px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  base: "16px"
  lg: "20px"
  xl: "24px"
  "2xl": "32px"
  "3xl": "48px"
components:
  card:
    backgroundColor: "{colors.surface}"
    borderColor: "{colors.border}"
    borderWidth: "1px"
    rounded: "{rounded.lg}"
    padding: "{spacing.lg}"
    shadow: "0 1px 2px 0 rgba(0, 0, 0, 0.03)"
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-inverse}"
    hoverBackgroundColor: "{colors.primary-hover}"
    rounded: "{rounded.md}"
    padding: "8px 16px"
    fontWeight: "500"
  button-secondary:
    backgroundColor: "{colors.surface}"
    borderColor: "{colors.border}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "8px 16px"
  table-header:
    backgroundColor: "{colors.background}"
    textColor: "{colors.text-secondary}"
    fontSize: "{typography.label.fontSize}"
    fontWeight: "600"
    padding: "10px 16px"
  table-row:
    borderBottomColor: "{colors.border}"
    padding: "12px 16px"
---

## 1. Overview & Filosofi Desain

Sistem E-Arsip ini dibangun dengan pendekatan **Action-Oriented Work Management** untuk memproses dokumen resmi (Surat Masuk, Surat Keluar, Memo Internal) serta tata kelola retensi dan kepatuhan arsip perusahaan.

Kombinasi warna dark forest green (`#0B2E28`) dan soft canvas neutral (`#F4F6F5`) menciptakan citra formal, stabil, dan ramah di mata untuk penggunaan berjam-jam oleh sekretariat, legal officer, maupun direksi.

---

## 2. Navigasi & Master Layout

Layout dirancang responsif dengan fixed sidebar kiri dan main content panel yang dapat di-scroll:

- **Sidebar Kiri (260px):**
  - Mengelompokkan 16 modul utama ke dalam 4 kategori hierarkis:
    1. _Alur Kerja Utama_: Dashboard, Registrasi Dokumen, Surat Masuk, Surat Keluar, Memo Internal.
    2. _Tata Kelola & Workflow_: Verifikasi, Approval, Disposisi & Tindak Lanjut.
    3. _Manajemen Arsip_: Master Berkas/Klasifikasi, Jadwal Retensi (JRA), Usul Pemusnahan, Audit Trail.
    4. _Pengaturan & Akses_: User & Role Management, Master Data, Search & Bantuan.
  - Active menu menggunakan bar vertikal warna Emerald (`#10B981`) di sisi kanan menu item dengan background semi-transparan putih `rgba(255, 255, 255, 0.08)`.

- **Top Navigation Bar:**
  - Menampilkan global search (dengan filter scope: No Surat, Isi Ringkas, Pengirim, Tahun Berkas).
  - Profiling ringkas: Nama Pengguna, Divisi/Unit Kerja, dan Switcher Role (contoh: Kepala Divisi vs Verifikator).

---

## 3. Komponen Utama & Pola Antarmuka (Pola Laravel + Livewire)

### 3.1. Kartu Antrean Tugas (Work Action Cards)

- Menggunakan pola Bento card 4 kolom di baris atas.
- Berisi: Label kategori pekerjaan, angka counter dokumen yang membutuhkan tindakan, status badge SLA (misal: "3 Melewati Batas SLA"), serta quick action link ke halaman bersangkutan.

### 3.2. Data Table Interaktif (Dokumen & Arsip)

- Didesain untuk kemudahan scanning visual:
  - **Nomor Dokumen:** Menggunakan font monospaced (`tabular-nums`) agar perbandingan digit rapi.
  - **Badge Status:** Memiliki format pill dengan pasangan warna status semantic (Draft, Menunggu Verifikasi, Menunggu Approval, Disetujui, Didisposisikan, Diarsipkan, Siap Dimusnahkan).
  - **SLA Countdown:** Menggunakan indikator warna teks (Hijau = aman, Kuning = < 24 jam, Merah = overdue).
  - **Action Cell:** Mengelompokkan tombol `Lihat`, `Proses / Disposisi`, dan dropdown `More Options`.

### 3.3. Lembar Disposisi & Form Workflow

- Tampilan detail dokumen menggunakan pola **Split View 50:50** atau **60:40**:
  - Kolom kiri: In-browser Document Viewer (PDF Preview dengan penanda tanda tangan elektronik / QR validation).
  - Kolom kanan: Tabbed panel form: Metadata Arsip, Lembar Disposisi Dinamis (penerima, instruksi tindak lanjut, tenggat waktu), dan Log Riwayat Disposisi.

### 3.4. Audit Trail & Jejak Digital

- Menampilkan timeline vertikal terurut berdasarkan waktu server (UTC+7):
  - Memuat tanggal & jam presisi, identitas akun eksekutor, alamat IP, deskripsi tindakan per dokumen, serta hash verifikasi integritas file.

---

## 4. Pola Implementasi Teknis (Tailwind CSS)

- **Cards:** `bg-white border border-slate-200 rounded-xl p-5 shadow-xs`
- **Sidebar Container:** `bg-[#0B2E28] text-slate-200 border-r border-[#134E44]`
- **Active Navigation Item:** `bg-white/10 text-white font-medium rounded-lg relative before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l`
- **Action Buttons:**
  - Primary: `bg-[#0B2E28] hover:bg-[#134E44] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors`
  - Subtle / Filter: `bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-medium`
- **Status Pills:** `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium`
- **Livewire Loading States:** Gunakan skeleton loading card (`animate-pulse bg-slate-100 rounded-lg`) pada setiap refresh komponen table atau widget data.

---

## 5. Aturan Absolut yang Tidak Boleh Dilanggar

1. **Tidak Ada Elemen Metrik Finansial/E-commerce:** Jangan menampilkan grafik penjualan, revenue, atau shopping cart; semua komponen data harus berupa indeks volume berkas, antrean disposisi, dan waktu kepatuhan SLA dokumen.
2. **Keterbacaan Dokumen Prioritas Tinggi:** Kontras warna teks pada background putih card wajib minimal 4.5:1 (WCAG AA). Teks dokumen tidak boleh menggunakan font dekoratif.
3. **Pemisahan Hak Akses:** Elemen aksi administratif (seperti _Setujui Usul Musnah_, _Tandatangani Dokumen_, _Terbitkan Disposisi_) harus memiliki state visual yang tegas (tersedia, disabled dengan tooltip penjelas hak akses, atau disembunyikan sesuai role pengguna).
