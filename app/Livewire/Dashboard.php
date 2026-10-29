<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $monitoringFilter = 'all'; // 'all', 'waiting', 'archived'
    public $monitoringSearch = '';

    // State Koreksi Metadata Draft (waiting_for_file)
    public $editDocId = null;
    public $editDocNumber = '';
    public $editDocType = '';
    public $editDocTypeRaw = '';
    public $editSubject = '';
    public $editDocumentDate = '';
    public $editRecipientName = '';
    public $editRecipientId = null;
    public $editSifat = 'biasa';
    public $editClassificationId = null;
    public $originalClassificationId = null;
    public $originalDocumentDate = '';
    public $yearChangeWarning = '';
    public $syncDocNumberWithClassification = false;
    public $newDocNumberPreview = '';
    public $editReason = '';

    // State Pembatalan Nomor Surat Resmi (Void)
    public $voidDocId = null;
    public $voidDocNumber = '';
    public $voidReason = '';

    // State Real-Time Sync & Notifikasi Staf
    public ?string $lastCheckedAt = null;
    public array $notifiedDocEvents = [];

    public function mount()
    {
        $this->lastCheckedAt = now()->toDateTimeString();
    }

    #[On('loan-status-changed')]
    public function handleStatusChanged(): void
    {
        // Re-render instan saat ada aksi dokumen lokal/global
    }

    public function checkStatusUpdates(): void
    {
        if ($this->editDocId || $this->voidDocId) {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $currentTime = now()->toDateTimeString();

        if ($this->lastCheckedAt && !$user->isSuperAdmin()) {
            $recentDocs = Document::where('created_by', $user->id)
                ->where('updated_at', '>=', $this->lastCheckedAt)
                ->orderBy('updated_at', 'asc')
                ->get();

            foreach ($recentDocs as $doc) {
                $timestamp = $doc->updated_at ? $doc->updated_at->timestamp : time();
                $eventKey = "{$doc->id}_{$doc->status}_{$timestamp}";

                if (in_array($eventKey, $this->notifiedDocEvents)) {
                    continue;
                }

                if ($doc->status === 'archived') {
                    $this->notifiedDocEvents[] = $eventKey;
                    $this->dispatch('notify', [
                        'message' => "Naskah {$doc->number} telah selesai di-scan dan resmi diarsipkan oleh Tapersip.",
                        'type' => 'success'
                    ]);
                } elseif ($doc->status === 'cancelled') {
                    $this->notifiedDocEvents[] = $eventKey;
                    $this->dispatch('notify', [
                        'message' => "Permohonan pembatalan naskah {$doc->number} telah disetujui oleh Tapersip (Void).",
                        'type' => 'info'
                    ]);
                } elseif ($doc->status === 'waiting_for_file' && !empty($doc->cancellation_rejection_notes)) {
                    $this->notifiedDocEvents[] = $eventKey;
                    $this->dispatch('notify', [
                        'message' => "Permohonan pembatalan naskah {$doc->number} ditolak Tapersip. Catatan: {$doc->cancellation_rejection_notes}",
                        'type' => 'warning'
                    ]);
                }
            }
        }

        $this->lastCheckedAt = $currentTime;
    }

    public function setMonitoringFilter($filter)
    {
        $this->monitoringFilter = $filter;
        $this->resetPage('monitoringPage');
    }

    public function updatedMonitoringSearch()
    {
        $this->resetPage('monitoringPage');
    }

    public function render()
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        $countIncoming = Document::where('type', 'incoming')->count();
        $countOutgoing = Document::where('type', 'outgoing')->count();
        $countInternal = Document::where('type', 'internal')->count();
        $countRetention = Document::whereNotNull('retention_end_date')->where('retention_end_date', '<=', now()->addDays(30))->count();

        // Hitung antrean yang menunggu upload (Personal per akun staf jika bukan Superadmin)
        $countWaitingQuery = Document::whereIn('status', ['waiting_for_file', 'cancellation_requested']);
        if (!$isSuperAdmin) {
            $countWaitingQuery->where('created_by', Auth::id());
        }
        $countWaiting = $countWaitingQuery->count();

        // Kueri Monitoring Arsip Dokumen:
        $monitoringQuery = Document::with(['unit', 'documentType', 'classification', 'createdBy', 'recipients.recipient']);

        // Filter status monitoring
        if ($this->monitoringFilter === 'waiting') {
            $monitoringQuery->whereIn('status', ['waiting_for_file', 'cancellation_requested']);
        } elseif ($this->monitoringFilter === 'archived') {
            $monitoringQuery->where('status', 'archived');
        } else {
            $monitoringQuery->where(function ($q) {
                $q->whereIn('status', ['waiting_for_file', 'cancellation_requested'])
                  ->orWhere(function ($qArchived) {
                      $qArchived->where('status', 'archived')
                                ->where('updated_at', '>=', now()->subDays(30));
                  });
            });
        }

        // Filter pencarian nomor, subjek, atau penerima
        if (!empty(trim($this->monitoringSearch))) {
            $term = trim($this->monitoringSearch);
            $monitoringQuery->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                  ->orWhere('subject', 'like', "%{$term}%")
                  ->orWhere('recipient_name', 'like', "%{$term}%");
            });
        }

        if (!$isSuperAdmin) {
            $monitoringQuery->where('created_by', Auth::id());
        }

        // Paginasi 5 data per halaman agar tabel tetap rapi dan tidak memanjang ke bawah
        $monitoringDocuments = $monitoringQuery
            ->orderBy('created_at', 'desc')
            ->paginate(5, ['*'], 'monitoringPage');

        $canViewAllActivities = $user ? $user->canViewAllActivities() : false;

        $activitiesQuery = AuditLog::with(['user', 'document']);
        if (!$canViewAllActivities) {
            $activitiesQuery->where('user_id', Auth::id());
        }

        $recentActivities = $activitiesQuery
            ->latest('created_at')
            ->take(5)
            ->get();

        // Evaluasi izin visibilitas kartu statistik via Role Matrix (Superadmin bypass otomatis)
        $canViewIncoming = $user ? $user->hasModuleAccess('card_incoming') : false;
        $canViewOutgoing = $user ? $user->hasModuleAccess('card_outgoing') : false;
        $canViewInternal = $user ? $user->hasModuleAccess('card_internal') : false;
        $canViewWaiting = $user ? $user->hasModuleAccess('card_waiting') : false;
        $canViewRetention = $user ? $user->hasModuleAccess('card_retention') : false;

        $isTapersipOrSuper = $isSuperAdmin || ($user && $user->isTapersip());
        $canViewLoan = $user ? ($user->hasModuleAccess('card_loans') || $user->hasModuleAccess('loan_circulation') || $user->hasModuleAccess('document_borrowing')) : false;

        $countBorrowed = 0;
        $countOverdue = 0;
        if ($canViewLoan) {
            $loanQuery = DocumentLoan::where('status', 'borrowed');
            if (!$isTapersipOrSuper) {
                $loanQuery->where('user_id', Auth::id());
            }
            $countBorrowed = (clone $loanQuery)->count();
            $countOverdue = (clone $loanQuery)->whereDate('expected_return_date', '<', now()->toDateString())->count();
        }

        $visibleCardsCount = (int)$canViewIncoming + (int)$canViewOutgoing + (int)$canViewInternal + (int)$canViewWaiting + (int)$canViewRetention + (int)$canViewLoan;

        $gridColsClass = match($visibleCardsCount) {
            1 => 'grid-cols-1 max-w-sm',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            5 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5',
            default => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6',
        };

        $classifications = Classification::whereNotNull('parent_id')->orderBy('code')->get();
        $availableUnits = Unit::where('status', 'active')->orderBy('name')->get();

        return view('livewire.dashboard', [
            'countIncoming' => $countIncoming,
            'countOutgoing' => $countOutgoing,
            'countInternal' => $countInternal,
            'countWaiting' => $countWaiting,
            'countRetention' => $countRetention,
            'countBorrowed' => $countBorrowed,
            'countOverdue' => $countOverdue,
            'monitoringDocuments' => $monitoringDocuments,
            'recentActivities' => $recentActivities,
            'classifications' => $classifications,
            'availableUnits' => $availableUnits,
            'isSuperAdmin' => $isSuperAdmin,
            'isTapersipOrSuper' => $isTapersipOrSuper,
            'canViewAllActivities' => $canViewAllActivities,
            'canViewIncoming' => $canViewIncoming,
            'canViewOutgoing' => $canViewOutgoing,
            'canViewInternal' => $canViewInternal,
            'canViewWaiting' => $canViewWaiting,
            'canViewRetention' => $canViewRetention,
            'canViewLoan' => $canViewLoan,
            'visibleCardsCount' => $visibleCardsCount,
            'gridColsClass' => $gridColsClass,
        ]);
    }

    public function openEditDraftModal($id)
    {
        $doc = Document::with(['createdBy', 'documentType', 'recipients'])->findOrFail($id);

        if ($doc->status !== 'waiting_for_file') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang berstatus Menunggu Upload yang dapat dikoreksi.', 'type' => 'error']);
            return;
        }

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && $doc->created_by !== Auth::id()) {
            $this->dispatch('notify', ['message' => 'Anda hanya berhak mengoreksi naskah yang Anda daftarkan sendiri.', 'type' => 'error']);
            return;
        }

        $this->editDocId = $doc->id;
        $this->editDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->editDocType = $doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus');
        $this->editDocTypeRaw = $doc->type;
        $this->editSubject = $doc->subject;
        $this->editDocumentDate = $doc->document_date ? $doc->document_date->format('Y-m-d') : date('Y-m-d');
        $this->originalDocumentDate = $this->editDocumentDate;
        $this->editRecipientName = $doc->recipient_name ?? '';
        $this->editRecipientId = $doc->recipients()->first()?->recipient_id;
        $this->editSifat = $doc->sifat ?? 'biasa';
        $this->editClassificationId = $doc->classification_id;
        $this->originalClassificationId = $doc->classification_id;
        $this->syncDocNumberWithClassification = false;
        $this->newDocNumberPreview = '';
        $this->yearChangeWarning = '';
        $this->editReason = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'modal-dashboard-edit-draft');
    }

    public function updatedEditClassificationId()
    {
        $this->calculateNewNumberPreview();
    }

    public function updatedEditDocumentDate()
    {
        $this->calculateNewNumberPreview();
    }

    protected function calculateNewNumberPreview()
    {
        $this->newDocNumberPreview = '';
        $this->syncDocNumberWithClassification = false;
        $this->yearChangeWarning = '';

        if (!$this->editDocId || !$this->editDocNumber || $this->editDocNumber === 'Tanpa Nomor') {
            return;
        }

        $candidateNumber = $this->editDocNumber;
        $hasChanges = false;

        // 1. Cek perubahan klasifikasi jika ada
        if ($this->editClassificationId && $this->originalClassificationId && $this->editClassificationId != $this->originalClassificationId) {
            $origClass = Classification::find($this->originalClassificationId);
            $newClass = Classification::find($this->editClassificationId);

            if ($origClass?->code && $newClass?->code && str_contains($candidateNumber, $origClass->code)) {
                $candidateNumber = str_replace($origClass->code, $newClass->code, $candidateNumber);
                $hasChanges = true;
            }
        }

        // 2. Cek perubahan tanggal dokumen (Bulan & Tahun)
        if ($this->originalDocumentDate && $this->editDocumentDate) {
            $origYear = date('Y', strtotime($this->originalDocumentDate));
            $newYear = date('Y', strtotime($this->editDocumentDate));
            $origMonth = date('m', strtotime($this->originalDocumentDate));
            $newMonth = date('m', strtotime($this->editDocumentDate));

            if ($origYear !== $newYear) {
                $this->yearChangeWarning = "Perubahan tahun ({$origYear} ke {$newYear}) tidak mengubah segmen tahun pada nomor surat demi menjaga integritas buku agenda tahunan. Jika salah tahun penerbitan, gunakan fitur Pembatalan Nomor (Void).";
            }

            if ($origMonth !== $newMonth) {
                if (str_contains($candidateNumber, '/' . $origMonth . '/')) {
                    $candidateNumber = str_replace('/' . $origMonth . '/', '/' . $newMonth . '/', $candidateNumber);
                    $hasChanges = true;
                } elseif (str_contains($candidateNumber, '/' . $origMonth . '-')) {
                    $candidateNumber = str_replace('/' . $origMonth . '-', '/' . $newMonth . '-', $candidateNumber);
                    $hasChanges = true;
                } elseif (str_contains($candidateNumber, '-' . $origMonth . '/')) {
                    $candidateNumber = str_replace('-' . $origMonth . '/', '-' . $newMonth . '/', $candidateNumber);
                    $hasChanges = true;
                }
            }
        }

        if ($hasChanges && $candidateNumber !== $this->editDocNumber) {
            $this->newDocNumberPreview = $candidateNumber;
            $this->syncDocNumberWithClassification = true;
        }
    }

    public function saveEditedDraft()
    {
        if (!$this->editDocId) return;

        $document = Document::with('recipients')->findOrFail($this->editDocId);

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && $document->created_by !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'editSubject' => 'required|string|max:255',
            'editDocumentDate' => 'required|date|before_or_equal:today',
            'editSifat' => 'required|in:biasa,penting,rahasia',
            'editClassificationId' => 'required|exists:classifications,id',
            'editReason' => 'required|string|min:3|max:500',
        ];

        if ($this->editDocTypeRaw === 'outgoing') {
            $rules['editRecipientName'] = 'required|string|max:255';
        } elseif ($this->editDocTypeRaw === 'internal') {
            $rules['editRecipientId'] = 'required|exists:units,id';
        }

        $this->validate($rules, [
            'editSubject.required' => 'Perihal dokumen wajib diisi.',
            'editDocumentDate.required' => 'Tanggal dokumen wajib diisi.',
            'editReason.required' => 'Mohon cantumkan alasan koreksi dokumen.',
            'editReason.min' => 'Alasan koreksi minimal 3 karakter.',
            'editRecipientName.required' => 'Tujuan penerima surat keluar wajib diisi.',
            'editRecipientId.required' => 'Departemen penerima surat khusus wajib dipilih.',
        ]);

        $oldNumber = $document->number;
        $newNumber = $oldNumber;

        if ($this->syncDocNumberWithClassification && $this->newDocNumberPreview && $this->newDocNumberPreview !== $oldNumber) {
            $newNumber = $this->newDocNumberPreview;
            $document->number = $newNumber;
        }

        $before = [
            'number' => $oldNumber,
            'subject' => $document->subject,
            'document_date' => $document->document_date?->format('Y-m-d'),
            'sifat' => $document->sifat,
            'classification_id' => $document->classification_id,
            'recipient' => $this->editDocTypeRaw === 'outgoing' ? $document->recipient_name : ($document->recipients()->first()?->recipient_id),
        ];

        $document->subject = $this->editSubject;
        $document->document_date = $this->editDocumentDate;
        $document->sifat = $this->editSifat;
        $document->classification_id = $this->editClassificationId;

        if ($this->editDocTypeRaw === 'outgoing') {
            $document->recipient_name = $this->editRecipientName;
        } elseif ($this->editDocTypeRaw === 'internal') {
            $document->recipients()->delete();
            $document->recipients()->create([
                'recipient_id' => $this->editRecipientId,
                'recipient_type' => Unit::class,
                'receipt_status' => 'unread',
            ]);
        }

        $document->save();

        $after = [
            'number' => $newNumber,
            'subject' => $document->subject,
            'document_date' => $this->editDocumentDate,
            'sifat' => $this->editSifat,
            'classification_id' => $this->editClassificationId,
            'recipient' => $this->editDocTypeRaw === 'outgoing' ? $this->editRecipientName : $this->editRecipientId,
        ];

        AuditLog::record(
            action: 'Koreksi Metadata Draft Naskah (Dashboard): ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'reason' => $this->editReason,
                'before' => $before,
                'after' => $after,
                'editor_id' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-dashboard-edit-draft');
        $this->dispatch('notify', [
            'message' => "Metadata draft naskah {$document->number} berhasil diperbarui.",
            'type' => 'success'
        ]);
        $this->reset(['editDocId', 'editDocNumber', 'editSubject', 'editDocumentDate', 'editRecipientName', 'editRecipientId', 'editReason', 'originalClassificationId', 'originalDocumentDate', 'yearChangeWarning', 'syncDocNumberWithClassification', 'newDocNumberPreview']);
    }

    public function openVoidDraftModal($id)
    {
        $doc = Document::findOrFail($id);
        if ($doc->status !== 'waiting_for_file') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang berstatus Menunggu Upload yang dapat dibatalkan.', 'type' => 'error']);
            return;
        }

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && $doc->created_by !== Auth::id()) {
            $this->dispatch('notify', ['message' => 'Anda hanya berhak membatalkan dokumen yang Anda daftarkan sendiri.', 'type' => 'error']);
            return;
        }

        $this->voidDocId = $doc->id;
        $this->voidDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->voidReason = '';
        $this->resetErrorBag('voidReason');

        $this->dispatch('open-modal', 'modal-dashboard-void-draft');
    }

    public function confirmVoidDraft()
    {
        if (!$this->voidDocId) return;

        $document = Document::findOrFail($this->voidDocId);

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && $document->created_by !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'voidReason' => 'required|string|min:3|max:500',
        ], [
            'voidReason.required' => 'Mohon cantumkan alasan pembatalan nomor surat.',
            'voidReason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        $isTapersipOrSuper = Auth::user()->isSuperAdmin() || Auth::user()->isTapersip();

        if ($isTapersipOrSuper) {
            $document->update([
                'status' => 'cancelled',
                'notes' => $this->voidReason,
                'cancelled_by' => Auth::id(),
            ]);

            AuditLog::record(
                action: 'Pembatalan Nomor Naskah Resmi (Dashboard Void): ' . ($document->number ?: $document->subject),
                documentId: $document->id,
                metadata: [
                    'reason' => $this->voidReason,
                    'cancelled_by' => Auth::id(),
                ]
            );

            $msg = "Nomor naskah {$document->number} resmi dibatalkan (void). Urutan nomor tetap utuh dalam register kantor.";
            $msgType = 'warning';
        } else {
            $document->update([
                'status' => 'cancellation_requested',
                'notes' => $this->voidReason,
                'cancellation_requested_at' => now(),
            ]);

            AuditLog::record(
                action: 'Permohonan Pembatalan Nomor Naskah: ' . ($document->number ?: $document->subject),
                documentId: $document->id,
                metadata: [
                    'reason' => $this->voidReason,
                    'requested_by' => Auth::id(),
                ]
            );

            $user = Auth::user();
            $unitName = $user?->unit?->name ? " ({$user->unit->name})" : '';
            $this->dispatch('desktop-notify', [
                'title' => 'Permohonan Batal Nomor Naskah',
                'body' => "{$user->name}{$unitName} mengajukan pembatalan naskah {$document->number}. Alasan: " . \Illuminate\Support\Str::limit($this->voidReason, 60),
                'url' => route('registry.cancelled.index', ['tab' => 'requests', 'highlight' => "doc-{$document->id}"]),
                'type' => 'cancellation_requested',
                'loan_code' => $document->number,
            ]);

            $msg = "Permohonan pembatalan naskah {$document->number} telah diajukan ke Tapersip untuk diverifikasi.";
            $msgType = 'info';
        }

        $this->dispatch('close-modal', 'modal-dashboard-void-draft');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => $msg,
            'type' => $msgType
        ]);
        $this->reset(['voidDocId', 'voidDocNumber', 'voidReason']);
    }
}
