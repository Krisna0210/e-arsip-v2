<?php

namespace App\Livewire\Registry;

use App\Models\Document;
use App\Models\DocumentLoan;
use Illuminate\Support\Facades\Auth;
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

    // State Tiket Cetak
    public $activeTicketLoan = null;

    // State Tab Sirkulasi Tapersip
    public $circulationSearch = '';
    public $circulationStatus = 'all';
    public $returnNotes = '';
    public $selectedLoanId = null;

    public function mount()
    {
        $this->borrowDate = date('Y-m-d');
        $this->expectedReturnDate = date('Y-m-d', strtotime('+7 days'));

        // Tentukan tab awal adaptif berdasarkan hak akses
        if ($this->canBorrow) {
            $this->activeTab = 'catalog';
        } elseif ($this->canManageCirculation) {
            $this->activeTab = 'circulation';
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

    public function openLoanModal($documentId)
    {
        if (!$this->canBorrow) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk meminjam dokumen.', 'type' => 'error']);
            return;
        }

        $doc = Document::with(['folder', 'unit'])->findOrFail($documentId);

        if ($doc->status !== 'archived') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang sudah diarsipkan yang dapat dipinjam.', 'type' => 'error']);
            return;
        }

        // Cek apakah sedang ada pinjaman aktif
        if ($doc->loans()->whereIn('status', ['requested', 'borrowed'])->exists()) {
            $this->dispatch('notify', ['message' => 'Fisik dokumen ini sedang dalam proses peminjaman.', 'type' => 'error']);
            return;
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

        $this->validate([
            'borrowDate' => 'required|date',
            'expectedReturnDate' => 'required|date|after_or_equal:borrowDate',
            'purpose' => 'required|string|min:5|max:1000',
        ], [
            'purpose.required' => 'Keperluan peminjaman fisik wajib diisi.',
            'purpose.min' => 'Keperluan peminjaman minimal 5 karakter.',
            'expectedReturnDate.after_or_equal' => 'Estimasi pengembalian tidak boleh sebelum tanggal pinjam.',
        ]);

        if (!$this->selectedDocument) {
            return;
        }

        // Cek kembali ketersediaan
        if ($this->selectedDocument->loans()->whereIn('status', ['requested', 'borrowed'])->exists()) {
            $this->dispatch('notify', ['message' => 'Fisik dokumen ini baru saja diajukan pinjam oleh pengguna lain.', 'type' => 'error']);
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
            'status' => 'requested',
        ]);

        \App\Models\AuditLog::record(
            action: "Pengajuan Pinjam Fisik: {$this->selectedDocument->number} (Tiket: {$loan->loan_code})",
            documentId: $this->selectedDocument->id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'borrow_date' => $this->borrowDate,
                'expected_return_date' => $this->expectedReturnDate,
                'purpose' => $this->purpose,
            ]
        );

        $this->dispatch('close-modal', 'modal-loan-request');

        // Buka tiket untuk dicetak
        $this->activeTicketLoan = $loan->load(['document.folder', 'document.unit', 'user.unit', 'user.position']);
        $this->dispatch('open-modal', 'modal-loan-ticket');
        $this->dispatch('notify', ['message' => "Permohonan pinjam berhasil diajukan dengan kode {$loan->loan_code}.", 'type' => 'success']);

        $this->reset(['selectedDocument', 'purpose']);
    }

    public function openTicketModal($loanId)
    {
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

        $this->dispatch('notify', ['message' => "Permohonan pinjam tiket {$loan->loan_code} berhasil dibatalkan.", 'type' => 'success']);
    }

    public function approveLoan($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $loan = DocumentLoan::with('document')->findOrFail($loanId);
        $loan->update([
            'status' => 'borrowed',
            'officer_id' => Auth::id(),
        ]);

        \App\Models\AuditLog::record(
            action: "Persetujuan Pinjam Berkas Fisik: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'borrower_id' => $loan->user_id,
            ]
        );

        $this->dispatch('notify', ['message' => "Hardcopy berhasil diserahkan. Status diubah menjadi 'Sedang Dipinjam'.", 'type' => 'success']);
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

        $loan = DocumentLoan::with('document')->findOrFail($this->selectedLoanId);
        $loan->update([
            'status' => 'returned',
            'returned_date' => now(),
            'notes' => $this->returnNotes ?: null,
            'officer_id' => Auth::id(),
        ]);

        \App\Models\AuditLog::record(
            action: "Pengembalian Berkas Fisik: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
                'notes' => $this->returnNotes,
            ]
        );

        $this->reset(['selectedLoanId', 'returnNotes']);
        $this->dispatch('close-modal', 'modal-loan-return');
        $this->dispatch('notify', ['message' => "Berkas fisik berhasil dikonfirmasi kembali ke loker.", 'type' => 'success']);
    }

    public function rejectLoan($loanId)
    {
        if (!$this->canManageCirculation) {
            abort(403, 'Akses ditolak.');
        }

        $loan = DocumentLoan::with('document')->findOrFail($loanId);
        $loan->update([
            'status' => 'rejected',
            'officer_id' => Auth::id(),
        ]);

        \App\Models\AuditLog::record(
            action: "Penolakan Pinjam Berkas Fisik: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
            documentId: $loan->document_id,
            metadata: [
                'loan_code' => $loan->loan_code,
            ]
        );

        $this->dispatch('notify', ['message' => "Permohonan pinjam telah ditolak.", 'type' => 'success']);
    }

    public function render()
    {
        // 1. Data Katalog Arsip (Tab 1)
        $catalogQuery = Document::with(['folder', 'unit', 'documentType', 'activeLoan.user'])
            ->where('status', 'archived');

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

        $catalogDocuments = $catalogQuery->orderBy('document_date', 'desc')->paginate(10, ['*'], 'catalogPage');

        // 2. Data Pinjaman Saya (Tab 2)
        $myLoans = DocumentLoan::with(['document.folder', 'document.unit', 'officer'])
            ->where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(10, ['*'], 'myLoansPage');

        // 3. Data Sirkulasi Tapersip (Tab 3) - Hanya diproses jika user memiliki izin
        $circulationLoans = null;
        if ($this->canManageCirculation) {
            $circulationQuery = DocumentLoan::with(['document.folder', 'document.unit', 'user.unit', 'officer']);

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

            if ($this->circulationStatus !== 'all') {
                $circulationQuery->where('status', $this->circulationStatus);
            }

            $circulationLoans = $circulationQuery->latest('created_at')->paginate(10, ['*'], 'circulationPage');
        }

        return view('livewire.registry.document-loan-index', [
            'catalogDocuments' => $catalogDocuments,
            'myLoans' => $myLoans,
            'circulationLoans' => $circulationLoans,
        ])->layout('components.layouts.app', ['title' => 'Cari & Pinjam Surat']);
    }
}
