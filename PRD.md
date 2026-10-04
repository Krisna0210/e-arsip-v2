# PRD — E-Arsip (Electronic Records Management System)

## 1. Ringkasan Produk

**E-Arsip** adalah sistem **Records Management** formal perusahaan untuk mengelola siklus hidup arsip secara terpusat, mulai dari pencatatan, penyimpanan, klasifikasi, verifikasi, persetujuan, distribusi, tindak lanjut, pengarsipan, audit, hingga retensi dan pemusnahan terkendali.

E-Arsip mencakup tiga jenis dokumen utama:

- Surat Masuk
- Surat Keluar
- Memo Internal

Sistem dibangun **on-premise** dengan:

- Frontend: Laravel Livewire
- Backend: Laravel
- Database: MySQL
- Primary archive file storage: NAS

---

## 2. Problem Statement

Pengelolaan surat dan memo perusahaan membutuhkan sistem terpusat karena:

1. Dokumen sulit ditemukan jika pencatatan dan metadata tidak konsisten.
2. Hak akses dokumen perlu dikendalikan berdasarkan sifat dokumen dan konteks pengguna.
3. Proses verifikasi, persetujuan, disposisi, dan tindak lanjut harus memiliki status dan jejak yang jelas.
4. Distribusi dokumen perlu dapat ditelusuri.
5. Perubahan dokumen dan aktivitas pengguna harus dapat diaudit.
6. Dokumen perlu memiliki siklus hidup dan retensi yang jelas.
7. Penyimpanan arsip perlu dipisahkan dari database metadata dan memiliki mekanisme backup/recovery.
8. Proses pemusnahan arsip tidak boleh dilakukan secara sembarangan dan harus memiliki otorisasi.

---

## 3. Target User

### 3.1 Kelompok Pengguna

- **Admin Tapersip**: mengelola pencatatan, metadata, file, arsip, distribusi, monitoring workflow, dan administrasi arsip.
- **Manajer**: memverifikasi dan menyetujui dokumen sesuai kewenangan.
- **Direksi**: memverifikasi dan menyetujui dokumen sesuai kewenangan.
- **User/Karyawan**: mencari dan melihat dokumen yang memang berhak diakses.

### 3.2 Persona

#### Persona 1 — Admin Tapersip / Records Officer

**Tujuan:** memastikan seluruh dokumen perusahaan tercatat, tersimpan, dapat ditelusuri, dan mengikuti siklus hidup arsip.

**Kebutuhan utama:**
- Mencatat dokumen.
- Mengunggah file PDF.
- Mengelola metadata.
- Memantau verifikasi, approval, disposisi, dan tindak lanjut.
- Mencari arsip.
- Mengelola arsip dan retensi.
- Melihat audit trail.

#### Persona 2 — Business User & Approver

Mewakili pengguna bisnis yang dapat berperan sebagai User/Karyawan, Manajer, atau Direksi sesuai role dan kewenangan yang diberikan.

**Tujuan:** menjalankan proses bisnis dokumen sesuai kewenangan dan mengakses dokumen yang berhak dilihat.

**Kebutuhan utama:**
- Membuat/mengedit draft jika memiliki hak.
- Melakukan verifikasi jika berwenang.
- Melakukan approval jika berwenang.
- Melakukan disposisi jika berwenang.
- Menindaklanjuti tugas.
- Melihat dan mengunduh dokumen sesuai akses.

---

## 4. Struktur Organisasi dan Otoritas

Struktur organisasi bersifat configurable:

**Direksi → Manajer Divisi → Departemen/Unit Kerja → Bagian/Jabatan**

Sistem harus membedakan:

- **Role**: menentukan permission sistem.
- **Position/Jabatan**: menentukan kewenangan bisnis.
- **Unit Kerja**: konteks organisasi dan akses.
- **Specific User Access**: akses eksplisit kepada pengguna tertentu jika diperlukan.

Posisi Manajer/Direksi tidak otomatis berarti pengguna memiliki permission approval. Permission tetap ditentukan melalui konfigurasi.

---

## 5. Goals

1. Menjadikan E-Arsip sebagai pusat pengelolaan arsip perusahaan.
2. Menstandarkan metadata dan klasifikasi arsip.
3. Menjamin akses dokumen sesuai sifat dan kewenangan.
4. Menyediakan workflow verifikasi, approval, disposisi, dan tindak lanjut yang dapat ditelusuri.
5. Menyediakan audit trail yang immutable bagi pengguna biasa.
6. Mempercepat pencarian dokumen.
7. Menyediakan lifecycle arsip dan retention management.
8. Menjamin backup dan recovery dengan RPO maksimal 24 jam dan RTO maksimal 4 jam.

---

## 6. Non-Goals

Untuk MVP tidak termasuk:

- OCR.
- Full-text search isi PDF.
- AI classification.
- AI summarization.
- Native mobile application.
- E-signature integration.
- WhatsApp/Telegram integration.
- LDAP/AD/SSO.
- Delegation.
- Integrasi langsung dengan sistem pengiriman eksternal.
- Automatic deletion saat masa retensi berakhir.
- Integrasi eksternal lain yang belum didefinisikan.

---

## 7. Scope dan Roadmap

### 7.1 MVP

MVP wajib mencakup:

1. Authentication dan authorization.
2. Master data.
3. Pencatatan dokumen dan metadata.
4. Upload/storage PDF.
5. Klasifikasi arsip.
6. Surat Masuk.
7. Surat Keluar.
8. Memo Internal.
9. Search/filter metadata.
10. Role dan access control.
11. Verification workflow.
12. Approval workflow.
13. Disposisi.
14. Tindak lanjut.
15. Distribusi.
16. Audit log/activity history.
17. Archiving.
18. Retention management.
19. Controlled destruction workflow.
20. Dashboard/monitoring dasar.
21. Backup/recovery.
22. Production readiness.

### 7.2 V2

- In-app notification.
- Email notification.
- OCR.
- Full-text PDF search.
- Bulk upload.
- Bulk metadata update.
- Document templates.
- Reporting/export yang lebih lengkap.
- Reminder.
- Delegation.
- Advanced versioning.
- LDAP/AD/SSO.

### 7.3 Later

- AI classification.
- AI summarization.
- Native mobile.
- E-signature.
- Integrasi eksternal.
- Advanced digital preservation.

---

## 8. Lifecycle Dokumen

### 8.1 Surat Masuk

**Diterima → Dicatat → Verifikasi → Disposisi → Tindak Lanjut → Arsip**

Rejection pada verifikasi:

**Ditolak/Perlu Perbaikan → Revisi → Diajukan Kembali**

### 8.2 Surat Keluar

**Draft → Verifikasi → Persetujuan → Penomoran → Dikirim → Arsip**

Posisi penomoran terhadap approval/penandatangan harus configurable sesuai aturan administrasi.

### 8.3 Memo Internal

**Draft → Persetujuan → Dikirim ke Unit Terkait/Direksi → Tindak Lanjut → Arsip**

Approval Memo tidak harus sama dengan Surat Keluar dan harus configurable berdasarkan jenis, tujuan, isi, dan kewenangan.

---

## 9. User Stories

### Pencatatan dan Penyimpanan

- Sebagai Admin Tapersip, saya ingin mencatat dokumen beserta metadata supaya arsip dapat ditemukan dan dikelola secara terstruktur.
- Sebagai pengguna yang berwenang, saya ingin mengunggah file PDF supaya dokumen memiliki arsip digital.
- Sebagai pengguna, saya ingin membuat draft metadata tanpa harus langsung mengunggah file supaya proses penyusunan dapat dilakukan bertahap.

### Verifikasi dan Approval

- Sebagai verifikator, saya ingin memverifikasi dokumen supaya hanya dokumen yang memenuhi ketentuan yang dapat melanjutkan workflow.
- Sebagai approver, saya ingin menyetujui atau menolak dokumen supaya keputusan memiliki jejak yang jelas.
- Sebagai drafter, saya ingin memperbaiki dokumen yang ditolak supaya dapat diajukan kembali tanpa kehilangan histori.

### Disposisi dan Tindak Lanjut

- Sebagai pejabat berwenang, saya ingin memberikan disposisi supaya dokumen dapat diteruskan kepada pihak yang tepat.
- Sebagai penerima disposisi, saya ingin melihat instruksi dan status tugas supaya tindak lanjut dapat dilakukan.
- Sebagai penanggung jawab, saya ingin mencatat hasil tindak lanjut supaya penyelesaian dokumen dapat dibuktikan.

### Search dan Access

- Sebagai pengguna, saya ingin mencari dokumen berdasarkan metadata supaya dokumen yang dibutuhkan cepat ditemukan.
- Sebagai pemilik kewenangan, saya ingin melihat dokumen sesuai sifat dan permission supaya informasi terlindungi.
- Sebagai administrator arsip, saya ingin melihat seluruh arsip supaya administrasi records management dapat dilakukan.

### Audit dan Retensi

- Sebagai auditor/administrator berwenang, saya ingin melihat histori aktivitas supaya perubahan dan akses dapat ditelusuri.
- Sebagai pengelola arsip, saya ingin melihat dokumen yang memasuki masa review retensi supaya keputusan retensi dapat dilakukan tepat waktu.

---

## 10. Functional Requirements

### FR-01 Authentication

- MVP menggunakan Laravel authentication berbasis database.
- Password harus disimpan menggunakan secure hashing.
- Password plaintext tidak boleh disimpan.
- LDAP/AD/SSO merupakan future scope.

### FR-02 Authorization

Permission minimal:

- View
- Download
- Create
- Edit
- Verify
- Approve
- Disposition
- Archive

Authorization mempertimbangkan kombinasi role, unit, recipient, dan explicit user access sesuai konfigurasi.

### FR-03 Sifat Dokumen

Sifat resmi:

- Biasa
- Penting
- Rahasia

Baseline access:

- **Biasa**: role + unit + recipient + permission.
- **Penting**: internal authorized user berdasarkan role/unit/recipient + permission.
- **Rahasia**: explicit authorization kepada user/unit/recipient + permission.

Matriks final permission per sifat masih menjadi open question.

### FR-04 Master Data

Master data minimal:

1. Unit Kerja — nama, kode.
2. Jenis Dokumen.
3. Klasifikasi Arsip — hierarchical.
4. Indeks Relatif — contoh HM.07 = Asuransi.
5. Role.
6. Position/Jabatan.
7. Permission.
8. Workflow/approval rule.
9. Retention policy.
10. Numbering rule.

Master data harus configurable.

### FR-05 Metadata Umum

Metadata umum:

- Nomor dokumen/surat.
- Tanggal dokumen.
- Pengirim/asal.
- Penerima/tujuan.
- Perihal.
- Unit kerja terkait.
- Klasifikasi arsip.
- Sifat.
- File dokumen.

Exact required/optional fields dapat berbeda per jenis dokumen dan tahap workflow.

### FR-06 Metadata Surat Masuk

Mandatory baseline:

- Nomor eksternal.
- Tanggal dokumen.
- Tanggal diterima.
- Pengirim.
- Perihal.
- Penerima/unit.
- Klasifikasi.
- Sifat.
- File PDF.

Tanggal diterima harus memenuhi validasi terhadap tanggal dokumen berdasarkan aturan bisnis.

Nomor eksternal tetap menggunakan nomor dari pihak pengirim. Internal agenda number belum menjadi kebutuhan wajib MVP.

### FR-07 Metadata Surat Keluar

Mandatory baseline:

- Nomor yang dihasilkan sistem.
- Tanggal.
- Unit pengirim.
- Penerima.
- Perihal.
- Klasifikasi.
- Sifat.
- File PDF.
- Penandatangan.

### FR-08 Metadata Memo

Mandatory baseline:

- Nomor yang dihasilkan sistem.
- Tanggal.
- Unit pengirim.
- Penerima.
- Perihal.
- Klasifikasi.
- Sifat.
- File PDF.

Memo dapat dikirim kepada banyak unit/user dalam satu dokumen.

### FR-09 Validasi Metadata

Minimal:

- Field mandatory harus terisi sesuai tahap.
- Tanggal tidak boleh future sebagai baseline kecuali aturan bisnis mengizinkan.
- Tanggal diterima Surat Masuk harus konsisten dengan tanggal dokumen.
- Sender/recipient tidak boleh kosong.
- Klasifikasi harus aktif.
- Sifat hanya Biasa/Penting/Rahasia.
- Nomor generated harus unique.
- Nomor manual Surat Masuk yang duplikat dapat diberi warning sesuai aturan yang telah ditetapkan.

### FR-10 File Upload dan Storage

- MVP hanya menerima PDF.
- Tidak ada arbitrary business maximum file size.
- Batas teknis harus configurable mengikuti NAS, PHP, web server, reverse proxy, dan timeout.
- User tidak mengakses NAS secara langsung.
- File disimpan pada NAS melalui aplikasi.

### FR-11 Surat Masuk Verification

Verifier dapat:

- Verify.
- Reject/return for revision.

Saat reject:

- Status menjadi Ditolak/Perlu Perbaikan.
- Reason wajib.
- Dokumen dikembalikan kepada pihak/unit pengaju.
- Dokumen tidak dihapus.
- Revisi dan resubmit dicatat di audit.
- Version history dipertahankan.

### FR-12 Disposition Surat Masuk

Disposisi dapat dilakukan oleh user yang memiliki permission.

Disposisi harus mencatat:

- Pemberi disposisi.
- Penerima.
- Timestamp.
- Instruksi/catatan.
- Status follow-up.

Chain dapat bersifat multi-level, contoh:

**Direksi → Manager → Staff**

atau:

**Manager → Staff**

### FR-13 Follow-up

Status minimal:

- Belum Ditindaklanjuti.
- Dalam Proses.
- Selesai.
- Dikembalikan.

Follow-up dapat memiliki:

- Assignee.
- Multiple assignee jika dikonfigurasi.
- Due date.
- Notes.
- Result document/attachment.
- Status history.

Baseline MVP: satu primary assignee + optional additional assignees.

### FR-14 Approval Matrix

Approval harus configurable berdasarkan:

- Jenis dokumen.
- Unit.
- Position.
- Authority.

Model yang didukung:

- Single approver.
- Sequential multi-level.
- Multi-approver.
- Parallel approval.
- Hybrid.

Drafter, issuer/penandatangan, recipient, dan approver harus disimpan sebagai entitas/peran yang berbeda.

### FR-15 Parallel Approval

Baseline:

- Semua required approver harus approve.
- Jika salah satu required approver reject, current approval cycle berhenti dan dokumen dikembalikan untuk revisi.
- Status masing-masing approver tetap tercatat.

### FR-16 Surat Keluar Numbering

Nomor dihasilkan sistem berdasarkan numbering rule yang configurable.

Contoh:

`1/-OPS-PM.05/VIII/2026`

Nomor harus unique.

Sequence reset tahunan.

Jika dokumen dibatalkan setelah nomor terbentuk:

- Nomor tidak digunakan ulang.
- Status menjadi VOID/CANCELLED.
- Reason dicatat.

Exact counter grouping tetap configurable dan belum dianggap final.

### FR-17 Surat Keluar Delivery

Metode:

1. Email/electronic.
2. Physical courier/expedition.
3. Direct handover.
4. Other electronic system.
5. Manual other.

Minimal recording:

- Method.
- Date.
- Recipient.

Proof of delivery:

- Optional pada MVP.
- Dapat diwajibkan berdasarkan method/type.

Multiple delivery attempts dapat dicatat.

### FR-18 Memo Multi-Recipient

Satu Memo dapat memiliki banyak recipient record.

Recipient dapat dibedakan menjadi:

- Primary.
- CC/Info.
- Follow-up/Action recipient.

Follow-up dapat ditrack per recipient jika diperlukan.

Receipt confirmation bersifat optional dan berbeda dengan follow-up.

### FR-19 Search

Search berbasis metadata pada MVP.

Filter minimal:

- Nomor.
- Date range.
- Jenis dokumen.
- Unit.
- Klasifikasi.
- Sifat.
- Pengirim.
- Penerima.
- Perihal.
- Status.
- Archive status.

Hasil pencarian harus sudah difilter berdasarkan permission.

Dokumen yang tidak berhak diakses tidak boleh muncul.

OCR dan full-text PDF search tidak termasuk MVP.

### FR-20 Access by Organization

- Admin Tapersip dapat melihat seluruh dokumen untuk kebutuhan administrasi arsip.
- Manajer dapat melihat dokumen dalam unitnya, dengan batasan khusus untuk Rahasia sesuai explicit permission.
- Direksi dapat melihat seluruh dokumen, sedangkan action rights tetap mengikuti permission.
- Specific user access dapat diberikan jika diperlukan dan harus diaudit.
- Specific access tidak boleh digunakan untuk melewati restriction inti.

### FR-21 User Movement

Jika user pindah unit:

- Access aktif mengikuti unit saat ini.
- Histori tetap mempertahankan actor/context historis.
- Access lama tidak otomatis tetap berlaku kecuali masih diberikan oleh role atau explicit permission.

### FR-22 Workflow Reassignment

Jika user yang memiliki task workflow kehilangan akses:

- Task tidak boleh macet.
- Reassignment dilakukan oleh authorized administrator.
- Reassignment tidak dilakukan otomatis.
- Reassignment diaudit.

Delegation bukan MVP.

### FR-23 Audit Log

Audit minimal mencatat:

- Login.
- View.
- Download.
- Upload.
- Metadata change.
- Revision.
- Verification.
- Approval.
- Rejection.
- Disposition.
- Archive.
- Retention action.
- Destruction action.

Untuk view minimal menyimpan user, document, dan time.

Download wajib diaudit dengan user, document, dan time.

Audit immutable untuk ordinary users.

Minimum retention audit: 5 tahun, configurable dan dapat mengikuti retention dokumen jika lebih panjang.

### FR-24 Versioning

Draft dapat diedit.

Final/Archive tidak boleh diedit langsung.

Koreksi Final/Archive:

1. Request correction.
2. Reason wajib.
3. Authorization/approval sesuai jenis perubahan.
4. Buat version baru.
5. Version lama dipertahankan dan immutable.
6. Version baru dapat kembali ke verification/approval sesuai aturan.
7. Semua perubahan diaudit.

Metadata administratif dapat diubah oleh authorized user dengan before/after value.

Daftar field core vs administrative dan approval matrix untuk correction masih perlu difinalisasi.

### FR-25 Retention

Baseline:

- Surat Masuk: 5 tahun.
- Surat Keluar: 5 tahun.
- Memo Internal: 5 tahun.

Retention configurable berdasarkan classification/JRA.

Retention tidak dihitung sekadar dari upload date.

Baseline start event:

- Surat Masuk: record/accepted date.
- Surat Keluar: final date.
- Memo: final date.

Saat retention berakhir:

**Masa Retensi Berakhir / Menunggu Review Retensi**

Tidak ada automatic deletion.

### FR-26 Retention Review

Outcome:

1. Extension.
2. Permanent archive.
3. Destruction.
4. Move to inactive archive.

Semua keputusan diaudit.

Dokumen yang mendekati expiry harus dapat ditampilkan untuk review.

### FR-27 Destruction

Flow:

**Retention Review → Proposal Destruction → Approval Pejabat Berwenang → Execution → Audit**

Admin/Pengelola Arsip dapat mengajukan.

Approval dilakukan oleh pejabat yang ditentukan company policy/configuration.

Executor harus memiliki authority.

System Administrator tidak otomatis menjadi destruction authority.

Metadata destruction minimal:

- Proposer.
- Approver.
- Executor.
- Time.
- Reason.
- Berita Acara (BA).

Setelah destruction, file tidak lagi tersedia sebagai active document, sedangkan metadata dan audit dipertahankan sesuai policy.

### FR-28 Archive

Final/Archive bersifat read-only untuk file.

Archive correction harus menggunakan versioning.

Archive metadata dapat dikoreksi oleh authorized user dengan audit before/after.

Core fields dapat memerlukan correction approval; exact matrix masih open.

---

## 11. Data Model Sketch

### User

- id
- username
- name
- email
- role_id
- unit_id
- position_id
- status
- password_hash

### Role

- id
- name
- status

### Permission

- id
- code
- name

### RolePermission

- role_id
- permission_id

### Unit

- id
- code
- name
- parent_id
- status

### Position

- id
- name
- level
- authority_config

### Document

- id
- document_type_id
- number
- document_date
- received_date
- sender
- recipient
- subject
- unit_id
- classification_id
- sifat
- status
- archive_status
- current_version_id
- retention_start_date
- retention_end_date
- created_by
- created_at
- updated_at

### DocumentVersion

- id
- document_id
- version_number
- file_reference
- created_by
- created_at
- reason
- status

### DocumentRecipient

- id
- document_id
- user_id/unit_id
- recipient_type
- receipt_status
- received_at

### DocumentAccess

- id
- document_id
- user_id/unit_id
- permission
- granted_by
- granted_at
- revoked_at

### WorkflowInstance

- id
- document_id
- workflow_type
- status
- current_step

### WorkflowStep

- id
- workflow_instance_id
- step_type
- sequence
- status
- assigned_to
- started_at
- completed_at

### Approval

- id
- document_id
- workflow_step_id
- approver_id
- status
- reason
- acted_at

### Disposition

- id
- document_id
- giver_id
- recipient_id
- instruction
- status
- created_at
- completed_at

### FollowUp

- id
- document_id
- disposition_id
- assignee_id
- status
- due_date
- notes
- completed_at

### Delivery

- id
- document_id
- method
- recipient
- delivery_date
- proof_file_reference
- notes

### RetentionPolicy

- id
- document_type_id
- classification_id
- retention_years
- start_event
- outcome_rule

### RetentionReview

- id
- document_id
- review_date
- outcome
- extension_until
- reason
- approved_by

### DestructionRecord

- id
- document_id
- proposer_id
- approver_id
- executor_id
- executed_at
- reason
- ba_file_reference

### AuditLog

- id
- user_id
- action
- document_id
- before_value
- after_value
- metadata
- created_at

---

## 12. Storage Architecture

Arsitektur penyimpanan:

**Laravel Application → MySQL + NAS**

MySQL menyimpan:

- Metadata.
- Workflow.
- User.
- Access control.
- Audit.
- Reference/master data.

NAS menyimpan:

- File arsip PDF.
- File version.
- Supporting file sesuai policy.

User tidak memiliki direct access ke NAS.

Protocol NAS: SMB/CIFS atau NFS sesuai infrastructure.

Folder NAS dapat menggunakan struktur tahun/unit/type/classification, tetapi folder structure bukan authorization mechanism.

---

## 13. Backup dan Disaster Recovery

Backup wajib mencakup:

- MySQL.
- NAS archive files.
- Application configuration/supporting components.

NAS bukan backup; NAS adalah primary storage.

Target:

- **RPO maksimal 24 jam**
- **RTO maksimal 4 jam**

MVP harus menyediakan:

- Scheduled backup.
- Backup monitoring.
- Restore capability.
- Periodic restore test.

Media, lokasi, frequency, dan retention backup mengikuti IT/security policy perusahaan.

---

## 14. Edge Cases dan Failure States

### Access

- User inactive.
- User pindah unit.
- Permission dicabut saat workflow berjalan.
- User kehilangan access tetapi masih memiliki task.
- Direct URL ke dokumen tanpa permission.
- Rahasia tanpa explicit access.

### Workflow

- Concurrent approval.
- Concurrent metadata update.
- Approver inactive.
- Approval rejection.
- Reassignment.
- Workflow timeout.
- Duplicate submission.

### File

- Upload gagal.
- File corrupt.
- File reference hilang.
- NAS unavailable.
- Storage full.
- Upload timeout.

### Numbering

- Duplicate generated number.
- Number generation concurrency.
- Document cancelled setelah numbering.
- Counter configuration berubah.

### Retention

- Dokumen memasuki expiry.
- Retention policy berubah.
- Review belum dilakukan.
- Destruction gagal.
- Destruction executed tetapi audit/BA gagal tersimpan.

### Backup/Recovery

- Backup gagal.
- Restore gagal.
- NAS unavailable.
- MySQL unavailable.
- Application configuration missing/corrupt.

---

## 15. Concurrency dan Integrity

Sistem harus mencegah:

- Duplicate document number.
- Double approval.
- Double disposition.
- Lost update.
- Dua proses mengubah status workflow secara tidak konsisten.
- Reuse nomor yang sudah pernah digunakan.

Database transaction dan locking harus digunakan pada operasi kritis.

---

## 16. Security Requirements

Minimal:

- Secure password hashing.
- Role-based authorization.
- Unit/recipient/access restriction.
- Permission-aware search.
- Audit access dan modification.
- Immutable audit untuk ordinary users.
- File access melalui aplikasi.
- User tidak direct access NAS.
- Validation upload.
- Session/security controls sesuai Laravel security baseline.
- Backup protection.
- Access to Rahasia harus lebih restrictive daripada Biasa/Penting sesuai policy.

---

## 17. Dashboard dan Monitoring

Dashboard MVP minimal menampilkan:

- Dokumen menunggu verifikasi.
- Dokumen menunggu approval.
- Dokumen ditolak/perlu revisi.
- Disposisi aktif.
- Follow-up belum selesai.
- Dokumen mendekati retention expiry.
- Retention review.
- Aktivitas penting sesuai permission.

---

## 18. Notifications

### MVP

Notification tidak menjadi channel wajib terpisah; status dan task harus dapat dilihat melalui sistem.

### V2

- In-app notification.
- Email notification.
- Reminder workflow.
- Reminder retention.

---

## 19. Success Metrics

### Adoption

- Persentase unit kerja yang menggunakan E-Arsip.
- Persentase dokumen yang tercatat di sistem.

### Search

- Search success rate.
- Median time-to-find document.

### Workflow

- Persentase workflow selesai sesuai SLA.
- Persentase dokumen stuck.
- Approval turnaround time.
- Follow-up completion rate.

### Governance

- Persentase dokumen memiliki metadata mandatory lengkap.
- Persentase aktivitas kritis memiliki audit trail.
- Unauthorized access incident.
- Duplicate numbering incident.

### Reliability

- Availability.
- Backup success rate.
- Restore test success rate.
- RPO/RTO compliance.

---

## 20. Definition of Done

E-Arsip dianggap selesai untuk production apabila:

1. Seluruh MVP feature telah diimplementasikan.
2. Workflow Surat Masuk berjalan end-to-end.
3. Workflow Surat Keluar berjalan end-to-end.
4. Workflow Memo berjalan end-to-end.
5. Authorization dan permission-aware search telah diuji.
6. Verification dan approval matrix berjalan.
7. Disposition dan follow-up berjalan.
8. Audit trail berjalan dan immutable untuk ordinary users.
9. Retention review berjalan.
10. Controlled destruction berjalan.
11. Versioning berjalan.
12. NAS storage terintegrasi.
13. Backup dan restore berhasil diuji.
14. RPO ≤ 24 jam.
15. RTO ≤ 4 jam.
16. Security testing selesai tanpa critical/blocker.
17. UAT selesai.
18. SOP operasional tersedia.
19. Training user selesai.
20. Monitoring tersedia.
21. Tidak ada blocker/critical issue.
22. Sistem siap digunakan seluruh perusahaan.

---

## 21. Final Open Questions

Hanya requirement yang belum cukup jelas yang dipertahankan sebagai open question.

### OQ-F01 — Matriks Permission berdasarkan Sifat

Untuk masing-masing:

- Biasa
- Penting
- Rahasia

perlu ditetapkan secara final apakah permission berikut dapat dilakukan:

- View
- Download
- Create
- Edit
- Verify
- Approve
- Disposition
- Archive

Khusus Rahasia, aturan explicit authorization sudah jelas, tetapi matriks action-level belum final.

### OQ-F02 — Jawaban OQ-06

Jawaban OQ-06 tidak dapat diidentifikasi secara pasti pada dokumen jawaban yang tersedia, sehingga requirement yang terkait belum boleh diasumsikan.

### OQ-F03 — Mapping Jawaban OQ-07/OQ-08

Bagian jawaban OQ-07/OQ-08 pada dokumen sumber tidak memiliki mapping heading-answer yang konsisten. Perlu konfirmasi agar requirement yang bersumber dari dua pertanyaan tersebut tidak salah ditempatkan.

### OQ-F04 — Titik Re-entry setelah Rejection

Setelah dokumen ditolak, perlu ditetapkan apakah:

- selalu kembali ke awal workflow;
- kembali ke step tertentu;
- atau ditentukan oleh konfigurasi workflow/jenis perubahan.

Rule umum revisi dan resubmit sudah jelas, tetapi titik re-entry belum final.

---

## 22. Product Decision Summary

| Area | Keputusan |
|---|---|
| Product type | Formal Records Management |
| Deployment | On-premise |
| Frontend | Laravel Livewire |
| Backend | Laravel |
| Database | MySQL |
| Primary file storage | NAS |
| File MVP | PDF |
| Document types | Surat Masuk, Surat Keluar, Memo Internal |
| Official sifat | Biasa, Penting, Rahasia |
| Authentication MVP | Laravel DB |
| Search MVP | Metadata only |
| OCR | V2 |
| Notification | V2 |
| Delegation | Future |
| Retention baseline | 5 tahun |
| Retention end | Review, bukan auto-delete |
| Destruction | Controlled workflow |
| Audit minimum | 5 tahun |
| RPO | ≤ 24 jam |
| RTO | ≤ 4 jam |
| Versioning | Required |
| Final/Archive file | Read-only |
| LDAP/SSO | Future |
| Production target | Seluruh perusahaan |

---

## 23. Catatan Implementasi

Dokumen ini merupakan baseline PRD. Konfigurasi yang memang dinyatakan configurable tidak boleh di-hardcode sebagai business rule tanpa keputusan konfigurasi resmi.

Khususnya:

- Approval matrix.
- Numbering rule dan counter grouping.
- Retention policy berdasarkan JRA.
- Access matrix berdasarkan sifat.
- Correction approval.
- Destruction authority.
- Workflow re-entry setelah rejection.
- Technical upload limit.
- Backup policy.

Setiap perubahan terhadap rule tersebut harus tercatat sebagai perubahan requirement/configuration dan dapat diaudit.
