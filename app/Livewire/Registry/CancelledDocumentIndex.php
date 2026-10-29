<?php

namespace App\Livewire\Registry;

use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CancelledDocumentIndex extends Component
{
    use WithPagination;

    public $activeTab = 'requests'; // 'requests' (Permohonan Batal) atau 'register' (Buku Register Batal)
    public $search = '';
    public $filterType = 'all'; // 'all', 'outgoing', 'internal'
    public $filterDateStart = '';
    public $filterDateEnd = '';

    // State Modal Konfirmasi Persetujuan Pembatalan
    public $approveDocId = null;
    public $approveDocNumber = '';

    // State Modal Penolakan Pembatalan
    public $rejectDocId = null;
    public $rejectDocNumber = '';
    public $rejectionNotes = '';

    protected $queryString = [
        'activeTab' => ['except' => 'requests'],
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
    ];

    #[On('loan-status-changed')]
    public function handleStatusChanged(): void
    {
        // Re-render saat ada perubahan status dokumen
    }

    public function mount(): void
    {
        if (request()->has('tab')) {
            $tab = request()->query('tab');
            if (in_array($tab, ['requests', 'register'])) {
                $this->activeTab = $tab;
            }
        } elseif (request()->has('activeTab')) {
            $tab = request()->query('activeTab');
            if (in_array($tab, ['requests', 'register'])) {
                $this->activeTab = $tab;
            }
        }

        // Smart Page Resolver untuk spotlight highlight dokumen batal
        $highlight = request()->query('highlight');
        if ($highlight && preg_match('/^doc-(\d+)$/', $highlight, $matches)) {
            $targetDocId = (int) $matches[1];
            $targetDoc = Document::find($targetDocId);

            if ($targetDoc) {
                if ($targetDoc->status === 'cancelled') {
                    $this->activeTab = 'register';
                } elseif ($targetDoc->status === 'cancellation_requested') {
                    $this->activeTab = 'requests';
                }

                if ($this->filterType !== 'all' && $this->filterType !== $targetDoc->type) {
                    $this->filterType = 'all';
                }

                if ($this->activeTab === 'requests') {
                    $rankQuery = Document::where('status', 'cancellation_requested');
                    if ($this->filterType !== 'all') {
                        $rankQuery->where('type', $this->filterType);
                    }
                    $position = $rankQuery->where(function ($q) use ($targetDoc) {
                        if ($targetDoc->cancellation_requested_at) {
                            $q->where('cancellation_requested_at', '<', $targetDoc->cancellation_requested_at)
                              ->orWhere(function ($q2) use ($targetDoc) {
                                  $q2->where('cancellation_requested_at', '=', $targetDoc->cancellation_requested_at)
                                     ->where('id', '<', $targetDoc->id);
                              });
                        } else {
                            $q->where('id', '<', $targetDoc->id);
                        }
                    })->count() + 1;
                } else {
                    $rankQuery = Document::where('status', 'cancelled');
                    if ($this->filterType !== 'all') {
                        $rankQuery->where('type', $this->filterType);
                    }
                    $position = $rankQuery->where(function ($q) use ($targetDoc) {
                        $q->where('updated_at', '>', $targetDoc->updated_at)
                          ->orWhere(function ($q2) use ($targetDoc) {
                              $q2->where('updated_at', '=', $targetDoc->updated_at)
                                 ->where('id', '>', $targetDoc->id);
                          });
                    })->count() + 1;
                }

                $targetPage = (int) ceil($position / 10);
                if ($targetPage > 1) {
                    $this->setPage($targetPage);
                }
            }
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterDateStart()
    {
        $this->resetPage();
    }

    public function updatingFilterDateEnd()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterDateStart', 'filterDateEnd']);
        $this->resetPage();
    }

    public function openApproveModal($id)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip()) {
            $this->dispatch('notify', ['message' => 'Hanya petugas Tapersip atau Superadmin yang berwenang menyetujui pembatalan.', 'type' => 'error']);
            return;
        }

        $doc = Document::findOrFail($id);
        if ($doc->status !== 'cancellation_requested') {
            $this->dispatch('notify', ['message' => 'Hanya naskah dalam status Permohonan Batal yang dapat disetujui.', 'type' => 'error']);
            return;
        }

        $this->approveDocId = $doc->id;
        $this->approveDocNumber = $doc->number ?: 'Tanpa Nomor';

        $this->dispatch('open-modal', 'modal-approve-cancellation');
    }

    public function confirmApproveCancellation()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip()) {
            abort(403, 'Akses ditolak.');
        }

        if (!$this->approveDocId) return;

        $document = Document::findOrFail($this->approveDocId);

        $document->update([
            'status' => 'cancelled',
            'cancelled_by' => Auth::id(),
        ]);

        AuditLog::record(
            action: 'Persetujuan Pembatalan Nomor Naskah Resmi (Void): ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'reason' => $document->notes,
                'approved_by' => Auth::id(),
                'requested_at' => $document->cancellation_requested_at?->toDateTimeString(),
            ]
        );

        $this->dispatch('close-modal', 'modal-approve-cancellation');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => "Permohonan pembatalan naskah {$document->number} resmi disetujui. Nomor resmi berstatus Void.",
            'type' => 'success',
        ]);
        $this->reset(['approveDocId', 'approveDocNumber']);
    }

    public function openRejectModal($id)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip()) {
            $this->dispatch('notify', ['message' => 'Hanya petugas Tapersip atau Superadmin yang berwenang menolak permohonan pembatalan.', 'type' => 'error']);
            return;
        }

        $doc = Document::findOrFail($id);
        if ($doc->status !== 'cancellation_requested') {
            $this->dispatch('notify', ['message' => 'Hanya naskah dalam status Permohonan Batal yang dapat diproses.', 'type' => 'error']);
            return;
        }

        $this->rejectDocId = $doc->id;
        $this->rejectDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->rejectionNotes = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'modal-reject-cancellation');
    }

    public function confirmRejectCancellation()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip()) {
            abort(403, 'Akses ditolak.');
        }

        if (!$this->rejectDocId) return;

        $this->validate([
            'rejectionNotes' => 'required|string|min:3|max:500',
        ], [
            'rejectionNotes.required' => 'Mohon cantumkan alasan penolakan pembatalan.',
            'rejectionNotes.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $document = Document::findOrFail($this->rejectDocId);

        $document->update([
            'status' => 'waiting_for_file',
            'cancellation_rejection_notes' => $this->rejectionNotes,
            'cancellation_requested_at' => null,
        ]);

        AuditLog::record(
            action: 'Penolakan Permohonan Pembatalan Naskah: ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'rejection_reason' => $this->rejectionNotes,
                'original_cancellation_reason' => $document->notes,
                'rejected_by' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-reject-cancellation');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => "Permohonan pembatalan naskah {$document->number} ditolak. Status dokumen dikembalikan ke Menunggu Upload.",
            'type' => 'info',
        ]);
        $this->reset(['rejectDocId', 'rejectDocNumber', 'rejectionNotes']);
    }

    public function render()
    {
        $pendingCount = Document::where('status', 'cancellation_requested')->count();
        $voidCount = Document::where('status', 'cancelled')->count();

        $query = Document::with(['unit', 'documentType', 'classification', 'createdBy.unit', 'cancelledBy', 'recipients.recipient']);

        if ($this->activeTab === 'requests') {
            $query->where('status', 'cancellation_requested');
        } else {
            $query->where('status', 'cancelled');
        }

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                  ->orWhere('subject', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%")
                  ->orWhereHas('createdBy', function ($qc) use ($term) {
                      $qc->where('name', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->filterDateStart) {
            $query->whereDate('document_date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->whereDate('document_date', '<=', $this->filterDateEnd);
        }

        $documents = $this->activeTab === 'requests'
            ? $query->orderBy('cancellation_requested_at', 'asc')->orderBy('id', 'asc')->paginate(10)
            : $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(10);

        return view('livewire.registry.cancelled-document-index', [
            'documents' => $documents,
            'pendingCount' => $pendingCount,
            'voidCount' => $voidCount,
            'isTapersipOrSuper' => Auth::user()->isSuperAdmin() || Auth::user()->isTapersip(),
        ])->layout('components.layouts.app', ['title' => 'Pembatalan Dokumen Dinas']);
    }
}
