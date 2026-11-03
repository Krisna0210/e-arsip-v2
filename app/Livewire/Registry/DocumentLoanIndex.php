<?php

namespace App\Livewire\Registry;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\User;
use App\Notifications\DocumentLoanApprovedNotification;
use App\Notifications\DocumentLoanRejectedNotification;
use App\Notifications\DocumentLoanReminderNotification;
use App\Notifications\DocumentLoanReturnedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentLoanIndex extends Component
{
    use WithPagination;

    // Tab navigasi: 'catalog', 'my_loans', 'circulation'
    public $activeTab = 'catalog';

    // State Filter & Pencarian Katalog
    public $search = '';
    public $filterType = '';
    public $filterSifat = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';
    public $isSearched = true; // Default tampilkan katalog terkini

    // State Pengajuan Pinjam (Modal)
    public $selectedDocument = null;
    public $borrowDate = '';
    public $expectedReturnDate = '';
    public $purpose = '';
    public string $borrowType = 'hardcopy'; // 'hardcopy' or 'softcopy' (mutually exclusive)

    // State Persetujuan Softcopy Berdurasi (Tapersip)
    public $approveLoanId = null;
    public $approveLoanCode = '';
    public $approveDocNumber = '';
    public $approveDocSubject = '';
    public $approveBorrowerName = '';
    public $approveDurationHours = 24;

    // State Pratinjau Dokumen Softcopy Terlindungi
    public $previewLoan = null;
    public $previewDocument = null;
    public $previewVersion = null;

    // State Tiket Cetak
    public $activeTicketLoan = null;

    // State Tab Sirkulasi Tapersip
    public $circulationSearch = '';
    public $circulationStatus = 'all';
    public $returnNotes = '';
    public $rejectNotes = '';
    public $selectedRejectLoan = null;
    public $selectedLoanId = null;

    public function mount()
    {
        $this->borrowDate = date('Y-m-d');
        $this->expectedReturnDate = date('Y-m-d', strtotime('+7 days'));

        $requestedTab = request()->query('tab');
        if ($requestedTab === 'circulation' && $this->canManageCirculation) {
            $this->activeTab = 'circulation';
        } elseif ($requestedTab && in_array($requestedTab, ['catalog', 'my_loans']) && $this->canBorrow) {
            $this->activeTab = $requestedTab;
        } elseif ($this->canBorrow) {
            $this->activeTab = 'catalog';
        } elseif ($this->canManageCirculation) {
            $this->activeTab = 'circulation';
        }

        // Smart Page Resolver untuk spotlight highlight tiket peminjaman di tab sirkulasi
        $highlight = request()->query('highlight');
        if ($highlight && preg_match('/^loan-(\d+)$/', $highlight, $matches)) {
            $targetLoanId = (int) $matches[1];
            $targetLoan = DocumentLoan::find($targetLoanId);

            if ($targetLoan && $this->canManageCirculation) {
                $this->activeTab = 'circulation';

                $rankQuery = DocumentLoan::query();
                $position = $rankQuery->where(function ($q) use ($targetLoan) {
                    $q->where('created_at', '>', $targetLoan->created_at)
                      ->orWhere(function ($q2) use ($targetLoan) {
                          $q2->where('created_at', '=', $targetLoan->created_at)
                             ->where('id', '>', $targetLoan->id);
                      });
                })->count() + 1;

                $targetPage = (int) ceil($position / 10);
                if ($targetPage > 1) {
                    $this->setPage($targetPage, 'circulationPage');
                }
            }
        }
    }

    public function getCanBorrowProperty(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        return $user->isSuperAdmin() || $user->hasModuleAccess('document_borrowing');
    }

    public function getCanManageCirculationProperty(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        return $user->isSuperAdmin() || $user->hasModuleAccess('loan_circulation');
    }

    public function updatedCirculationSearch()
    {
        $this->resetPage('circulationPage');
    }

    public function updatedCirculationStatus()
    {
        $this->resetPage('circulationPage');
    }

    public function setTab($tab)
    {
        // Proteksi tab Sirkulasi Tapersip
        if ($tab === 'circulation' && !$this->canManageCirculation) {
            $tab = $this->canBorrow ? 'catalog' : 'circulation';
        }

        // Proteksi tab Katalog & Pinjaman Saya
        if (in_array($tab, ['catalog', 'my_loans']) && !$this->canBorrow) {
            $tab = $this->canManageCirculation ? 'circulation' : 'catalog';
        }

        $this->activeTab = $tab;
        $this->resetPage('catalogPage');
        $this->resetPage('myLoansPage');
        $this->resetPage('circulationPage');
    }

    public function performSearch()
    {
        $this->isSearched = true;
        $this->resetPage('catalogPage');
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterSifat', 'filterDateStart', 'filterDateEnd']);
        $this->isSearched = true;
        $this->resetPage('catalogPage');
    }

    #[On('loan-status-changed')]
    public function handleLoanStatusChanged()
    {
        // Otomatis me-render ulang kueri tabel sirkulasi, pinjaman saya, dan katalog saat ada perubahan status
    }

    /**
     * Memeriksa apakah pengguna berhak melihat/meminjam dokumen berdasarkan sifat kerahasiaan & divisi
     */
    public function canAccessDocument(Document $doc, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        // 1. Superadmin & Petugas Tapersip memiliki hak akses penuh ke seluruh dokumen
        if ($user->isSuperAdmin() || $user->isTapersip()) {
            return true;
        }

        // 2. Pembuat dokumen selalu berhak mengakses dokumennya sendiri
        if ($doc->created_by === $user->id) {
            return true;
        }

        // 3. Dokumen Biasa: Terbuka untuk seluruh staf kantor
        if ($doc->sifat === 'biasa') {
            return true;
        }

        // 4. Dokumen Rahasia: Hanya jika user memiliki izin view untuk rahasia
        if ($doc->sifat === 'rahasia') {
            return $user->hasDocumentPermission('view', 'rahasia');
        }

        // 5. Dokumen Penting:
        if ($doc->sifat === 'penting') {
            if ($user->hasDocumentPermission('view', 'penting')) {
                return true;
            }

            // A. Cek jika unit kerja user tercatat sebagai pihak penerima dokumen
            $isRecipient = $doc->recipients()
                ->where('recipient_type', \App\Models\Unit::class)
                ->where(function ($qr) use ($user) {
                    $qr->where('recipient_id', $user->unit_id);
                    $userDivisionId = $user->getDivision()?->id;
                    if ($userDivisionId) {
                        $qr->orWhere('recipient_id', $userDivisionId);
                    }
                })
                ->exists();

            if ($isRecipient) {
                return true;
            }

            // Resolusi Departemen dan Divisi Asal Dokumen
            $creator = $doc->createdBy ?? ($doc->created_by ? \App\Models\User::with('unit.parent')->find($doc->created_by) : null);
            $docDepartmentId = null;
            if ($doc->unit && $doc->unit->type === 'department') {
                $docDepartmentId = $doc->unit_id;
            } elseif ($creator && $creator->unit && $creator->unit->type === 'department') {
                $docDepartmentId = $creator->unit_id;
            }

            $docDivisionId = $doc->unit?->getDivision()?->id
                ?? $creator?->getDivision()?->id
                ?? ($doc->unit && $doc->unit->type === 'division' ? $doc->unit_id : null);

            $userUnit = $user->unit;
            $userIsDivision = $userUnit && $userUnit->type === 'division';
            $userDivisionId = $user->getDivision()?->id;

            // B. Pewarisan Vertikal ke Atas:
            // Akun yang melekat pada level Divisi dapat melihat semua naskah penting buatan divisinya & departemen di bawahnya
            if ($userIsDivision && $userDivisionId && $docDivisionId && $userDivisionId === $docDivisionId) {
                return true;
            }

            // C. Isolasi Horizontal Antar-Departemen (Surat Keluar & Surat Khusus):
            // Jika dokumen berasal dari suatu departemen, hanya staf dari departemen yang sama yang dapat melihat
            if ($docDepartmentId && $user->unit_id === $docDepartmentId) {
                return true;
            }

            // D. Jika dokumen tidak memiliki departemen spesifik (diterbitkan langsung di level divisi),
            // dan user berada di divisi yang sama
            if (!$docDepartmentId && $docDivisionId && $userDivisionId === $docDivisionId) {
                return true;
            }

            // E. Surat Masuk (incoming): jika ditujukan ke departemen atau divisi user
            if ($doc->type === 'incoming') {
                if ($doc->unit_id === $user->unit_id || ($userIsDivision && $docDivisionId === $userDivisionId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function openLoanModal($documentId)
    {
        if (!$this->canBorrow) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk meminjam dokumen.', 'type' => 'error']);
            return;
        }

        $this->settleExpiredSoftcopyLoans();

        $doc = Document::with(['folder', 'unit'])->findOrFail($documentId);

        // Security check: Lapisan Hak Akses Kerahasiaan & Divisi
        if (!$this->canAccessDocument($doc)) {
            $this->dispatch('notify', [
                'message' => 'Akses ditolak: Dokumen ini bersifat rahasia/terbatas untuk divisi lain.',
                'type' => 'error'
            ]);
            return;
        }

        if ($doc->status !== 'archived') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang sudah diarsipkan yang dapat dipinjam.', 'type' => 'error']);
            return;
        }

        // Cek ketersediaan format dokumen
        if (!$doc->available_hardcopy && !$doc->available_softcopy) {
            $this->dispatch('notify', ['message' => 'Dokumen ini belum tersedia baik dalam format fisik maupun digital.', 'type' => 'error']);
            return;
        }

        // Cek apakah dokumen sedang ada pinjaman aktif (softcopy maupun hardcopy)
        if ($doc->loans()->whereIn('status', ['requested', 'borrowed'])->exists()) {
            $this->dispatch('notify', ['message' => 'Dokumen ini sedang dalam proses peminjaman.', 'type' => 'error']);
            return;
        }

        // Default tipe pinjam
        if ($doc->available_hardcopy) {
            $this->borrowType = 'hardcopy';
        } elseif ($doc->available_softcopy) {
            $this->borrowType = 'softcopy';
        } else {
            $this->borrowType = '';
        }

        $this->selectedDocument = $doc;
        $this->borrowDate = date('Y-m-d');
        $this->expectedReturnDate = date('Y-m-d', strtotime('+7 days'));
        $this->purpose = '';

        $this->dispatch('open-modal', 'modal-loan-request');
    }

    public function submitLoanRequest()
    {
        if (!$this->canBorrow) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk meminjam dokumen.');
        }

        $this->settleExpiredSoftcopyLoans();

        if (!$this->selectedDocument || !$this->canAccessDocument($this->selectedDocument)) {
            abort(403, 'Akses ditolak: Dokumen ini bersifat rahasia/terbatas untuk divisi lain.');
        }

        $this->validate([
            'borrowType' => 'required|in:hardcopy,softcopy',
            'borrowDate' => 'required|date',
            'expectedReturnDate' => 'required|date|after_or_equal:borrowDate',
            'purpose' => 'required|string|min:5|max:1000',
        ], [
            'borrowType.required' => 'Pilih salah satu tipe dokumen yang ingin dipinjam (Hardcopy atau Softcopy).',
            'purpose.required' => 'Keperluan peminjaman wajib diisi.',
            'purpose.min' => 'Keperluan peminjaman minimal 5 karakter.',
            'expectedReturnDate.after_or_equal' => 'Estimasi pengembalian tidak boleh sebelum tanggal pinjam.',
        ]);

        if (!$this->selectedDocument) {
            return;
        }

        if ($this->borrowType === 'hardcopy' && !$this->selectedDocument->available_hardcopy) {
            $this->addError('borrowType', 'Berkas fisik (hardcopy) tidak tersedia untuk dokumen ini.');
            return;
        }

        if ($this->borrowType === 'softcopy' && !$this->selectedDocument->available_softcopy) {
            $this->addError('borrowType', 'Berkas digital (softcopy) belum tersedia untuk dokumen ini.');
            return;
        }

        // Cek kembali ketersediaan jika sedang dipinjam softcopy maupun hardcopy
        if ($this->selectedDocument->loans()->whereIn('status', ['requested', 'borrowed'])->exists()) {
            $this->dispatch('notify', ['message' => 'Dokumen ini sedang dalam proses peminjaman oleh staf lain.', 'type' => 'error']);
            $this->dispatch('close-modal', 'modal-loan-request');
            return;
        }

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $this->selectedDocument->id,
            'user_id' => Auth::id(),
            'borrow_date' => $this->borrowDate,
            'expected_return_date' => $this->expectedReturnDate,
            'purpose' => $this->purpose,
            'loan_type' => $this->borrowType,
            'status' => 'requested',
        ]);

        $actionName = $this->borrowType === 'softcopy'
            ? "Pengajuan Pinjam Softcopy: {$this->selectedDocument->number} (Tiket: {$loan->loan_code})"
            : "Pengajuan Pinjam Fisik: {$this->selectedDocument->number} (Tiket: {$loan->loan_code})";

        \App\Models\AuditLog::record(
            action: $actionName,
            documentId: $this->selectedDocument->id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'loan_type' => $this->borrowType,
                'borrow_date' => $this->borrowDate,
                'expected_return_date' => $this->expectedReturnDate,
                'purpose' => $this->purpose,
            ]
        );

        $this->dispatch('close-modal', 'modal-loan-request');
        $this->dispatch('loan-status-changed');

        // Alihkan staf ke tab Pinjaman Saya dan tampilkan notifikasi konfirmasi
        $this->activeTab = 'my_loans';
        $this->resetPage('myLoansPage');

        $successMsg = $this->borrowType === 'softcopy'
            ? "Permohonan pinjam berkas digital (softcopy) berhasil diajukan dengan kode {$loan->loan_code}. Menunggu persetujuan dan pengaturan durasi akses dari Tapersip."
            : "Permohonan pinjam berkas fisik (hardcopy) berhasil diajukan dengan kode {$loan->loan_code}. Petugas Tapersip sedang menyiapkan berkas fisik dan bukti peminjaman Anda.";

        $this->dispatch('notify', [
            'message' => $successMsg,
            'type' => 'success'
        ]);

        $this->reset(['selectedDocument', 'purpose']);
    }

    public function openTicketModal($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak: Hanya petugas Tapersip yang berwenang mencetak tiket bukti peminjaman fisik.');
        }

        $loan = DocumentLoan::with(['document.folder', 'document.unit', 'user.unit', 'user.position', 'officer'])->findOrFail($loanId);
        $this->activeTicketLoan = $loan;
        $this->dispatch('open-modal', 'modal-loan-ticket');
    }

    public function cancelLoanRequest($loanId)
    {
        $loan = DocumentLoan::with('document')->findOrFail($loanId);

        // Verifikasi kepemilikan atau Superadmin
        if ($loan->user_id !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak: Anda hanya dapat membatalkan permohonan pinjam milik Anda sendiri.');
        }

        // Hanya permohonan yang masih berstatus requested yang dapat dibatalkan
        if ($loan->status !== 'requested') {
            $this->dispatch('notify', ['message' => 'Hanya permohonan yang masih berstatus menunggu yang dapat dibatalkan.', 'type' => 'error']);
            return;
        }

        $loan->update([
            'status' => 'cancelled',
            'notes' => 'Dibatalkan oleh pemohon',
        ]);

        \App\Models\AuditLog::record(
            action: "Pembatalan Pinjam Fisik: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'user_id' => Auth::id(),
            ]
        );

        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', ['message' => "Permohonan pinjam tiket {$loan->loan_code} berhasil dibatalkan.", 'type' => 'success']);
    }

    public function approveLoan($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $loan = DocumentLoan::with(['document.documentType', 'user'])->findOrFail($loanId);

        if ($loan->isSoftcopy()) {
            $this->approveLoanId = $loan->id;
            $this->approveLoanCode = $loan->loan_code;
            $this->approveDocNumber = $loan->document?->number ?: 'Naskah';
            $this->approveDocSubject = $loan->document?->subject ?: '-';
            $this->approveBorrowerName = $loan->user?->name ?: 'Pemohon';
            $this->approveDurationHours = 24;
            $this->dispatch('open-modal', 'modal-approve-softcopy-loan');
            return;
        }

        $this->executeApproveLoan($loan);
    }

    public function executeApproveSoftcopyLoan()
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'approveDurationHours' => 'required|integer|in:2,6,24,72,168',
        ]);

        $loan = DocumentLoan::with(['document.documentType', 'user'])->findOrFail($this->approveLoanId);
        $duration = (int) $this->approveDurationHours;
        $expiresAt = now()->addHours($duration);

        $this->executeApproveLoan($loan, $duration, $expiresAt);
        $this->dispatch('close-modal', 'modal-approve-softcopy-loan');
    }

    protected function executeApproveLoan(DocumentLoan $loan, ?int $durationHours = null, ?\Carbon\Carbon $expiresAt = null)
    {
        $loan->update([
            'status' => 'borrowed',
            'officer_id' => Auth::id(),
            'duration_hours' => $durationHours,
            'access_expires_at' => $expiresAt,
        ]);

        $actionType = $loan->isSoftcopy()
            ? ($loan->isHardcopy() ? 'Persetujuan Pinjam Berkas Fisik & Softcopy' : 'Persetujuan Pinjam Akses Softcopy')
            : 'Persetujuan Pinjam Berkas Fisik';

        \App\Models\AuditLog::record(
            action: "{$actionType}: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'borrower_id' => $loan->user_id,
                'loan_type' => $loan->loan_type,
                'duration_hours' => $durationHours,
                'access_expires_at' => $expiresAt?->toDateTimeString(),
            ]
        );

        // Kirim notifikasi email persetujuan peminjaman ke staf pemohon (fail-safe langsung dengan penanganan kegagalan)
        $borrower = $loan->user;
        $officer = Auth::user();
        $docNumber = $loan->document?->number ?: 'Naskah';

        if ($borrower && filter_var($borrower->email, FILTER_VALIDATE_EMAIL)) {
            try {
                $borrower->notify(new DocumentLoanApprovedNotification($loan, $officer));
                $emailStatus = 'success';
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email persetujuan pinjaman: ' . $e->getMessage());
                $emailStatus = 'failed';
            }
        } else {
            $emailStatus = 'no_email';
        }

        $mediaLabel = $loan->isSoftcopy()
            ? "Izin pratinjau softcopy aktif {$durationHours} jam"
            : "Hardcopy berhasil diserahkan";

        $notifTitle = $loan->isSoftcopy() ? 'Peminjaman Softcopy Disetujui' : 'Peminjaman Disetujui';
        $notifBody = $loan->isSoftcopy()
            ? "Izin akses digital berkas {$docNumber} (Tiket: {$loan->loan_code}) telah aktif ({$durationHours} jam). Email konfirmasi terkirim ke {$borrower->name}."
            : "Berkas {$docNumber} (Tiket: {$loan->loan_code}) telah diserahkan. Email konfirmasi telah terkirim ke {$borrower->name} ({$borrower->email}).";

        // Tampilkan Floating In-App Visual Toast dengan notifikasi status email
        if ($emailStatus === 'success') {
            $this->dispatch('desktop-notify', [
                'title' => "{$notifTitle} & Email Terkirim",
                'body' => $notifBody,
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_success',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "{$mediaLabel}. Status 'Sedang Dipinjam'. Notifikasi email dikirim ke {$borrower->name}.";
            $notifyType = 'success';
        } elseif ($emailStatus === 'failed') {
            $this->dispatch('desktop-notify', [
                'title' => "{$notifTitle} (Email Gagal)",
                'body' => "Status diubah ke 'Sedang Dipinjam', namun email konfirmasi ke {$borrower->email} gagal terkirim. Harap hubungi pemohon secara manual.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "{$mediaLabel} ('Sedang Dipinjam'), namun email notifikasi gagal terkirim ke {$borrower->email}.";
            $notifyType = 'warning';
        } else {
            $this->dispatch('desktop-notify', [
                'title' => "{$notifTitle} (Tanpa Email)",
                'body' => "Berkas {$docNumber} (Tiket: {$loan->loan_code}) telah disetujui. Akun pemohon belum memiliki alamat email yang valid.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "{$mediaLabel}. Akun pemohon tidak memiliki alamat email terdaftar.";
            $notifyType = 'info';
        }

        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', ['message' => $successMsg, 'type' => $notifyType]);
    }

    public function openPreviewModal($loanId)
    {
        $loan = DocumentLoan::with(['document.currentVersion', 'user'])->findOrFail($loanId);

        if ($loan->user_id !== Auth::id() && !Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip()) {
            abort(403, 'Akses ditolak: Anda tidak memiliki akses ke pratinjau ini.');
        }

        if (!$loan->isSoftcopy()) {
            $this->dispatch('notify', ['message' => 'Pinjaman ini hanya mencakup berkas fisik (hardcopy).', 'type' => 'error']);
            return;
        }

        if ($loan->status !== 'borrowed') {
            $this->dispatch('notify', ['message' => 'Dokumen hanya dapat dipratinjau saat status pinjaman disetujui / aktif.', 'type' => 'error']);
            return;
        }

        if ($loan->access_expires_at && now()->gt($loan->access_expires_at)) {
            $this->dispatch('notify', ['message' => 'Masa izin akses digital telah kedaluwarsa. Silakan ajukan pinjaman baru.', 'type' => 'error']);
            return;
        }

        $version = $loan->document?->currentVersion;
        if (!$version) {
            $this->dispatch('notify', ['message' => 'Berkas digital dokumen ini belum diunggah.', 'type' => 'error']);
            return;
        }

        $this->previewLoan = $loan;
        $this->previewDocument = $loan->document;
        $this->previewVersion = $version;

        $this->dispatch('open-modal', 'modal-preview-loan-doc');
    }

    public function openReturnModal($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $this->selectedLoanId = $loanId;
        $this->returnNotes = '';
        $this->dispatch('open-modal', 'modal-loan-return');
    }

    public function confirmReturn()
    {
        if (!$this->canManageCirculation || !$this->selectedLoanId) {
            return;
        }

        $loan = DocumentLoan::with(['document.documentType', 'user'])->findOrFail($this->selectedLoanId);
        $loan->update([
            'status' => 'returned',
            'returned_date' => now(),
            'notes' => $this->returnNotes ?: null,
            'officer_id' => Auth::id(),
        ]);

        $actionName = ($loan->isSoftcopy() ? "Penyelesaian Pinjaman Softcopy: " : "Pengembalian Berkas Fisik: ")
            . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})";

        \App\Models\AuditLog::record(
            action: $actionName,
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'loan_type' => $loan->loan_type,
                'notes' => $this->returnNotes,
            ]
        );

        // Kirim notifikasi email pengembalian / penyelesaian berkas ke staf pemohon (fail-safe)
        $borrower = $loan->user;
        $officer = Auth::user();
        $docNumber = $loan->document?->number ?: 'Naskah';
        $emailStatus = 'no_email';

        if ($borrower && filter_var($borrower->email, FILTER_VALIDATE_EMAIL)) {
            try {
                $borrower->notify(new DocumentLoanReturnedNotification($loan, $officer, $this->returnNotes));
                $emailStatus = 'success';
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email pengembalian pinjaman: ' . $e->getMessage());
                $emailStatus = 'failed';
            }
        }

        $mediaSuccess = $loan->isSoftcopy()
            ? "Peminjaman softcopy tiket {$loan->loan_code} telah diselesaikan."
            : "Berkas fisik berhasil dikonfirmasi kembali ke loker.";

        $notifTitle = $loan->isSoftcopy() ? 'Peminjaman Softcopy Selesai' : 'Berkas Kembali & Email Terkirim';
        $notifBody = $loan->isSoftcopy()
            ? "Peminjaman softcopy {$docNumber} (Tiket: {$loan->loan_code}) telah diselesaikan. Email konfirmasi terkirim ke {$borrower->name}."
            : "Berkas {$docNumber} (Tiket: {$loan->loan_code}) telah kembali ke loker. Email bukti pengembalian terkirim ke {$borrower->name} ({$borrower->email}).";

        // Tampilkan Floating In-App Visual Toast
        if ($emailStatus === 'success') {
            $this->dispatch('desktop-notify', [
                'title' => $notifTitle,
                'body' => $notifBody,
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_returned_success',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "{$mediaSuccess} Email konfirmasi dikirim ke {$borrower->name}.";
            $notifyType = 'success';
        } elseif ($emailStatus === 'failed') {
            $this->dispatch('desktop-notify', [
                'title' => "{$notifTitle} (Email Gagal)",
                'body' => "Peminjaman tiket {$loan->loan_code} telah diselesaikan, namun email notifikasi gagal terkirim ke {$borrower->email}.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "{$mediaSuccess} Namun email bukti gagal terkirim ke {$borrower->email}.";
            $notifyType = 'warning';
        } else {
            $this->dispatch('desktop-notify', [
                'title' => "{$notifTitle} (Tanpa Email)",
                'body' => "Peminjaman tiket {$loan->loan_code} telah diselesaikan. Akun pemohon tidak memiliki alamat email valid.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_returned_success',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = $mediaSuccess;
            $notifyType = 'info';
        }

        $this->reset(['selectedLoanId', 'returnNotes']);
        $this->dispatch('close-modal', 'modal-loan-return');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', ['message' => $successMsg, 'type' => $notifyType]);
    }

    public function openRejectModal($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $this->selectedLoanId = $loanId;
        $this->selectedRejectLoan = DocumentLoan::with(['document', 'user'])->findOrFail($loanId);
        $this->rejectNotes = '';
        $this->resetErrorBag('rejectNotes');
        $this->dispatch('open-modal', 'modal-loan-reject');
    }

    public function confirmReject()
    {
        if (!$this->canManageCirculation || !$this->selectedLoanId) {
            return;
        }

        $this->validate([
            'rejectNotes' => 'required|string|min:3|max:500',
        ], [
            'rejectNotes.required' => 'Mohon cantumkan alasan penolakan pinjaman.',
            'rejectNotes.min' => 'Alasan penolakan minimal 3 karakter.',
            'rejectNotes.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $loan = DocumentLoan::with(['document.documentType', 'user'])->findOrFail($this->selectedLoanId);
        $loan->update([
            'status' => 'rejected',
            'notes' => $this->rejectNotes,
            'officer_id' => Auth::id(),
        ]);

        $mediaText = $loan->isSoftcopy() ? 'Softcopy' : 'Fisik';

        \App\Models\AuditLog::record(
            action: "Penolakan Pinjam Berkas {$mediaText}: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'loan_type' => $loan->loan_type,
                'reason' => $this->rejectNotes,
                'borrower_id' => $loan->user_id,
            ]
        );

        // Kirim notifikasi email penolakan pinjaman ke staf pemohon (fail-safe)
        $borrower = $loan->user;
        $officer = Auth::user();
        $docNumber = $loan->document?->number ?: 'Naskah';
        $emailStatus = 'no_email';

        if ($borrower && filter_var($borrower->email, FILTER_VALIDATE_EMAIL)) {
            try {
                $borrower->notify(new DocumentLoanRejectedNotification($loan, $officer, $this->rejectNotes));
                $emailStatus = 'success';
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email penolakan pinjaman: ' . $e->getMessage());
                $emailStatus = 'failed';
            }
        }

        $rejectTitle = $loan->isSoftcopy() ? 'Permohonan Pinjam Softcopy Ditolak & Email Terkirim' : 'Permohonan Ditolak & Email Terkirim';

        // Tampilkan Floating In-App Visual Toast
        if ($emailStatus === 'success') {
            $this->dispatch('desktop-notify', [
                'title' => $rejectTitle,
                'body' => "Permohonan pinjam {$mediaText} tiket {$loan->loan_code} ({$docNumber}) telah ditolak. Email pemberitahuan terkirim ke {$borrower->name} ({$borrower->email}).",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_rejected_success',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "Permohonan pinjam {$mediaText} tiket {$loan->loan_code} ditolak. Email dikirim ke {$borrower->name}.";
            $notifyType = 'success';
        } elseif ($emailStatus === 'failed') {
            $this->dispatch('desktop-notify', [
                'title' => "Permohonan Pinjam {$mediaText} Ditolak (Email Gagal)",
                'body' => "Permohonan pinjam {$mediaText} tiket {$loan->loan_code} ditolak, namun email notifikasi gagal terkirim ke {$borrower->email}.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "Permohonan ditolak, namun email gagal terkirim ke {$borrower->email}.";
            $notifyType = 'warning';
        } else {
            $this->dispatch('desktop-notify', [
                'title' => "Permohonan Pinjam {$mediaText} Ditolak",
                'body' => "Permohonan pinjam {$mediaText} tiket {$loan->loan_code} ditolak. Pemohon tidak memiliki alamat email valid.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $successMsg = "Permohonan pinjam {$mediaText} tiket {$loan->loan_code} telah ditolak.";
            $notifyType = 'info';
        }

        $this->reset(['selectedLoanId', 'selectedRejectLoan', 'rejectNotes']);
        $this->dispatch('close-modal', 'modal-loan-reject');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', ['message' => $successMsg, 'type' => $notifyType]);
    }

    public function rejectLoan($loanId)
    {
        $this->openRejectModal($loanId);
    }

    public function sendReminderEmail($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $loan = DocumentLoan::with(['document.documentType', 'user'])->findOrFail($loanId);
        $borrower = $loan->user;
        $officer = Auth::user();
        $docNumber = $loan->document?->number ?: 'Naskah';

        if (!$borrower || !filter_var($borrower->email, FILTER_VALIDATE_EMAIL)) {
            $this->dispatch('desktop-notify', [
                'title' => 'Pengingat Gagal (Tanpa Email)',
                'body' => "Peminjam berkas {$docNumber} (Tiket: {$loan->loan_code}) belum memiliki alamat email yang valid.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $this->dispatch('notify', ['message' => 'Akun pemohon tidak memiliki alamat email terdaftar.', 'type' => 'warning']);
            return;
        }

        try {
            $borrower->notify(new DocumentLoanReminderNotification($loan, $officer));
            $emailStatus = 'success';
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email pengingat pinjaman: ' . $e->getMessage());
            $emailStatus = 'failed';
        }

        $actionName = ($loan->isSoftcopy() ? "Kirim Pengingat Batas Izin Softcopy: " : "Kirim Pengingat Pinjam Berkas Fisik: ")
            . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})";

        \App\Models\AuditLog::record(
            action: $actionName,
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'loan_type' => $loan->loan_type,
                'borrower_email' => $borrower->email,
                'email_status' => $emailStatus,
            ]
        );

        if ($emailStatus === 'success') {
            $isOverdue = $loan->isSoftcopy()
                ? ($loan->access_expires_at && now()->gt($loan->access_expires_at))
                : ($loan->expected_return_date && now()->isAfter($loan->expected_return_date));

            $title = $isOverdue ? 'Pemberitahuan Terlambat Terkirim' : 'Email Pengingat Berhasil Terkirim';
            $body = $loan->isSoftcopy()
                ? "Email pengingat batas waktu izin digital berkas {$docNumber} telah dikirim ke {$borrower->name} ({$borrower->email})."
                : "Email pengingat batas pengembalian berkas fisik {$docNumber} telah dikirim ke {$borrower->name} ({$borrower->email}).";

            $this->dispatch('desktop-notify', [
                'title' => $title,
                'body' => $body,
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_reminder_success',
                'loan_code' => $loan->loan_code,
            ]);
            $this->dispatch('notify', ['message' => "Email pengingat berhasil dikirim ke {$borrower->name}.", 'type' => 'success']);
        } else {
            $this->dispatch('desktop-notify', [
                'title' => 'Pengingat Gagal Terkirim',
                'body' => "Gagal mengirim email pengingat berkas tiket {$loan->loan_code} ke {$borrower->email}.",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_approved_mail_failed',
                'loan_code' => $loan->loan_code,
            ]);
            $this->dispatch('notify', ['message' => "Gagal mengirim email pengingat ke {$borrower->email}.", 'type' => 'error']);
        }
    }

    protected function settleExpiredSoftcopyLoans(): void
    {
        DocumentLoan::where('loan_type', 'softcopy')
            ->where('status', 'borrowed')
            ->whereNotNull('access_expires_at')
            ->where('access_expires_at', '<=', now())
            ->update([
                'status' => 'returned',
                'returned_date' => now(),
                'notes' => 'Masa izin akses digital telah berakhir (Selesai otomatis oleh sistem)',
            ]);
    }

    public function render()
    {
        $this->settleExpiredSoftcopyLoans();

        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || $user->isTapersip());
        $canViewRahasia = $isPrivileged || ($user && $user->hasDocumentPermission('view', 'rahasia'));

        // 1. Data Katalog Arsip (Tab 1)
        $catalogQuery = Document::with(['folder', 'unit.parent', 'documentType', 'activeLoan.user', 'createdBy.unit', 'recipients.recipient'])
            ->where('status', 'archived')
            ->where('archive_status', '!=', 'disposed');

        // Batasan Hak Akses Berlapis untuk Staf Biasa (Bukan Superadmin / Tapersip)
        if (!$isPrivileged && $user) {
            $userUnit = $user->unit;
            $userIsDivision = $userUnit && $userUnit->type === 'division';
            $userDivisionId = $user->getDivision()?->id;
            $userUnitId = $user->unit_id;
            $userId = $user->id;
            $canViewPenting = $user->hasDocumentPermission('view', 'penting');

            $catalogQuery->where(function ($q) use ($userUnitId, $userDivisionId, $userIsDivision, $userId, $canViewRahasia, $canViewPenting) {
                // 1. Dokumen Biasa selalu terlihat oleh seluruh staf, atau naskah buatan sendiri
                $q->where('sifat', 'biasa')
                  ->orWhere('created_by', $userId);

                // 2. Dokumen Rahasia (hanya jika akun memiliki izin khusus view rahasia)
                if ($canViewRahasia) {
                    $q->orWhere('sifat', 'rahasia');
                }

                // 3. Dokumen Penting (Hierarchical Upward Inheritance & Horizontal Department Isolation):
                if ($canViewPenting) {
                    $q->orWhere('sifat', 'penting');
                } else {
                    $q->orWhere(function ($qPenting) use ($userUnitId, $userDivisionId, $userIsDivision) {
                        $qPenting->where('sifat', 'penting')
                            ->where(function ($qRules) use ($userUnitId, $userDivisionId, $userIsDivision) {
                                // A. Akun level Divisi:
                                // Melihat seluruh naskah penting yang diterbitkan oleh divisinya,
                                // atau departemen-departemen di bawah divisinya,
                                // atau dibuat oleh karyawan di bawah naungan divisinya
                                if ($userIsDivision && $userDivisionId) {
                                    $qRules->where('unit_id', $userDivisionId)
                                        ->orWhereHas('unit', function ($qu) use ($userDivisionId) {
                                            $qu->where('parent_id', $userDivisionId);
                                        })
                                        ->orWhereHas('createdBy.unit', function ($quc) use ($userDivisionId) {
                                            $quc->where('id', $userDivisionId)
                                                ->orWhere('parent_id', $userDivisionId);
                                        });
                                }

                                // B. Akun level Departemen:
                                // Hanya dapat melihat naskah jika berasal dari departemen yang sama persis
                                // via unit_id dokumen ATAU via unit_id pembuat dokumen (creator)
                                if ($userUnitId) {
                                    $qRules->orWhere(function ($qDept) use ($userUnitId) {
                                        $qDept->where('unit_id', $userUnitId)
                                            ->orWhereHas('createdBy', function ($qc) use ($userUnitId) {
                                                $qc->where('unit_id', $userUnitId);
                                            });
                                    });
                                }

                                // C. Pihak penerima dokumen dinas (recipients)
                                if ($userUnitId) {
                                    $qRules->orWhereHas('recipients', function ($qr) use ($userUnitId, $userDivisionId) {
                                        $qr->where('recipient_type', \App\Models\Unit::class)
                                           ->where(function ($qRec) use ($userUnitId, $userDivisionId) {
                                               $qRec->where('recipient_id', $userUnitId);
                                               if ($userDivisionId) {
                                                   $qRec->orWhere('recipient_id', $userDivisionId);
                                               }
                                           });
                                    });
                                }
                            });
                    });
                }
            });
        }

        if ($this->search) {
            $catalogQuery->where(function ($q) {
                $q->where('number', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('sender', 'like', '%' . $this->search . '%')
                  ->orWhere('recipient_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $catalogQuery->where('type', $this->filterType);
        }

        if ($this->filterSifat) {
            $catalogQuery->where('sifat', $this->filterSifat);
        }

        if ($this->filterDateStart) {
            $catalogQuery->whereDate('document_date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $catalogQuery->whereDate('document_date', '<=', $this->filterDateEnd);
        }

        $catalogDocuments = $catalogQuery->with(['activeLoan.user'])->orderBy('document_date', 'desc')->paginate(10, ['*'], 'catalogPage');

        // 2. Data Pinjaman Saya (Tab 2)
        $myLoans = DocumentLoan::with(['document.folder', 'document.unit', 'document.documentType', 'document.recipients.recipient', 'officer'])
            ->where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(10, ['*'], 'myLoansPage');

        // 3. Data Sirkulasi Tapersip (Tab 3) - Hanya diproses jika user memiliki izin
        $circulationLoans = null;
        if ($this->canManageCirculation) {
            $circulationQuery = DocumentLoan::with(['document.folder', 'document.unit', 'document.documentType', 'document.recipients.recipient', 'user.unit', 'officer']);

            if ($this->circulationSearch) {
                $circulationQuery->where(function ($q) {
                    $q->where('loan_code', 'like', '%' . $this->circulationSearch . '%')
                      ->orWhereHas('document', function ($qd) {
                          $qd->where('number', 'like', '%' . $this->circulationSearch . '%')
                             ->orWhere('subject', 'like', '%' . $this->circulationSearch . '%');
                      })
                      ->orWhereHas('user', function ($qu) {
                          $qu->where('name', 'like', '%' . $this->circulationSearch . '%');
                      });
                });
            }

            if ($this->circulationStatus === 'overdue') {
                $circulationQuery->where('status', 'borrowed')
                    ->where('expected_return_date', '<', now()->toDateString());
            } elseif ($this->circulationStatus !== 'all') {
                $circulationQuery->where('status', $this->circulationStatus);
            }

            $circulationLoans = $circulationQuery->latest('created_at')->latest('id')->paginate(10, ['*'], 'circulationPage');
        }

        return view('livewire.registry.document-loan-index', [
            'catalogDocuments' => $catalogDocuments,
            'myLoans' => $myLoans,
            'circulationLoans' => $circulationLoans,
            'canViewRahasia' => $canViewRahasia,
            'isPrivileged' => $isPrivileged,
        ])->layout('components.layouts.app', ['title' => 'Cari & Pinjam Surat']);
    }
}
