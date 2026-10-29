<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Document;
use App\Models\DestructionRecord;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DestructionIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $activeTab = 'expired';

    // Batch Selection on Tab 1
    public array $selectedDocIds = [];
    public bool $selectAll = false;

    // State Modal Usulan
    public $selectedDocId = null;
    public $selectedDocNumber = '';
    public $selectedDocSubject = '';
    public $selectedDocRetentionEnd = '';
    public array $selectedDocsSummary = [];
    public $proposalReason = '';

    // State Modal Eksekusi
    public ?string $activeBatchCode = null;
    public $activeRecordId = null;
    public $activeRecordDocNumber = '';
    public $activeRecordDocSubject = '';
    public $baFile = null;

    // State Modal Penolakan
    public ?string $rejectBatchCode = null;
    public $rejectRecordId = null;
    public $rejectRecordDocNumber = '';
    public $rejectionReason = '';

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedDocIds = $this->expiredDocuments->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedDocIds = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selectedDocIds = [];
        $this->selectAll = false;
    }

    public function getExpiredDocumentsProperty()
    {
        return Document::with(['documentType', 'classification', 'unit', 'folder', 'recipients.recipient'])
            ->whereDate('retention_end_date', '<=', now())
            ->where('archive_status', '!=', 'disposed')
            ->whereDoesntHave('destructionRecords', function($q) {
                $q->whereIn('status', ['proposed', 'approved', 'executed']);
            })
            ->paginate(10, ['*'], 'expiredPage');
    }

    public function getProposalsProperty()
    {
        // Ambil daftar batch unik menggunakan pagination
        $batchPaginator = DestructionRecord::select('batch_code', DB::raw('MAX(id) as latest_id'))
            ->groupBy('batch_code')
            ->orderByDesc('latest_id')
            ->paginate(10, ['*'], 'proposalsPage');

        $codes = collect($batchPaginator->items())->pluck('batch_code')->filter()->toArray();

        if (empty($codes)) {
            $batchPaginator->setCollection(collect());
            return $batchPaginator;
        }

        $allRecords = DestructionRecord::with(['document.documentType', 'proposer', 'approver', 'executor'])
            ->whereIn('batch_code', $codes)
            ->get()
            ->groupBy('batch_code');

        $batchItems = collect($batchPaginator->items())->map(function ($item) use ($allRecords) {
            $code = $item->batch_code;
            $records = $allRecords->get($code, collect());
            $lead = $records->first();
            if (!$lead) return null;

            return (object) [
                'batch_code' => $code,
                'lead_id' => $lead->id,
                'status' => $lead->status,
                'reason' => $lead->reason,
                'ba_file_path' => $lead->ba_file_path,
                'created_at' => $lead->created_at,
                'executed_at' => $lead->executed_at,
                'proposer' => $lead->proposer,
                'approver' => $lead->approver,
                'executor' => $lead->executor,
                'records' => $records,
                'documents' => $records->pluck('document')->filter()->values(),
                'count' => $records->count(),
            ];
        })->filter()->values();

        $batchPaginator->setCollection($batchItems);
        return $batchPaginator;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->clearSelection();
        $this->resetPage('expiredPage');
        $this->resetPage('proposalsPage');
    }

    public function openProposeModal($docId = null)
    {
        if ($docId) {
            $this->selectedDocIds = [(string) $docId];
        }

        if (empty($this->selectedDocIds)) {
            $this->dispatch('notify', ['message' => 'Pilih sekurang-kurangnya 1 naskah untuk diajukan.', 'type' => 'error']);
            return;
        }

        $docs = Document::whereIn('id', $this->selectedDocIds)->get();
        $this->selectedDocsSummary = $docs->map(fn($d) => [
            'id' => $d->id,
            'number' => $d->number ?: 'Tanpa Nomor',
            'subject' => $d->subject,
            'retention_end_date' => $d->retention_end_date ? $d->retention_end_date->format('d M Y') : '-',
        ])->toArray();

        // Support backward compatibility
        $this->selectedDocId = (int) $this->selectedDocIds[0];
        $this->selectedDocNumber = $this->selectedDocsSummary[0]['number'] ?? '';
        $this->selectedDocSubject = $this->selectedDocsSummary[0]['subject'] ?? '';
        $this->selectedDocRetentionEnd = $this->selectedDocsSummary[0]['retention_end_date'] ?? '';

        $this->proposalReason = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-propose');
    }

    public function submitProposal()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && !Auth::user()->hasModuleAccess('destruction')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki wewenang untuk mengajukan usulan pemusnahan arsip.', 'type' => 'error']);
            return;
        }

        $this->validate([
            'selectedDocIds' => 'required|array|min:1',
            'proposalReason' => 'required|string|min:3|max:500',
        ], [
            'selectedDocIds.required' => 'Pilih sekurang-kurangnya 1 naskah untuk diajukan.',
            'selectedDocIds.min' => 'Pilih sekurang-kurangnya 1 naskah untuk diajukan.',
            'proposalReason.required' => 'Mohon cantumkan alasan usulan pemusnahan arsip.',
            'proposalReason.min' => 'Alasan usulan pemusnahan minimal 3 karakter.',
            'proposalReason.max' => 'Alasan usulan pemusnahan maksimal 500 karakter.',
        ]);

        $docs = Document::whereIn('id', $this->selectedDocIds)
            ->whereDate('retention_end_date', '<=', now())
            ->where('archive_status', '!=', 'disposed')
            ->whereDoesntHave('destructionRecords', function($q) {
                $q->whereIn('status', ['proposed', 'approved', 'executed']);
            })
            ->get();

        if ($docs->count() !== count($this->selectedDocIds)) {
            $this->dispatch('notify', ['message' => 'Sebagian naskah yang dipilih tidak memenuhi syarat habis retensi atau sudah diusulkan.', 'type' => 'error']);
            return;
        }

        $batchCode = DestructionRecord::generateBatchCode();

        foreach ($this->selectedDocIds as $docId) {
            DestructionRecord::create([
                'batch_code' => $batchCode,
                'document_id' => $docId,
                'proposer_id' => Auth::id(),
                'status' => 'proposed',
                'reason' => $this->proposalReason,
            ]);
        }

        $docNumbers = Document::whereIn('id', $this->selectedDocIds)->pluck('number')->filter()->implode(', ');
        $count = count($this->selectedDocIds);
        $action = $count > 1
            ? "Pengajuan Usulan Pemusnahan Arsip Kolektif ({$batchCode}): " . ($docNumbers ?: 'Naskah Terpilih')
            : "Pengajuan Usulan Pemusnahan Arsip: " . ($docNumbers ?: 'Dokumen');

        AuditLog::record(
            action: $action,
            documentId: (int) $this->selectedDocIds[0],
            metadata: [
                'batch_code' => $batchCode,
                'document_ids' => $this->selectedDocIds,
                'count' => $count,
                'reason' => $this->proposalReason,
                'proposer_id' => Auth::id(),
            ]
        );

        $count = count($this->selectedDocIds);
        session()->flash('success', "Usulan pemusnahan {$count} arsip ({$batchCode}) berhasil diajukan.");
        $this->dispatch('notify', ['message' => "Usulan pemusnahan {$count} arsip ({$batchCode}) berhasil diajukan.", 'type' => 'success']);
        $this->dispatch('close-modal', 'modal-propose');
        $this->reset(['selectedDocId', 'selectedDocNumber', 'selectedDocSubject', 'selectedDocRetentionEnd', 'selectedDocsSummary', 'selectedDocIds', 'selectAll', 'proposalReason']);
    }

    public function approveProposal($target)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->hasModuleAccess('approve_destruction')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menyetujui pemusnahan arsip.', 'type' => 'error']);
            return;
        }

        $records = is_numeric($target)
            ? ($rec = DestructionRecord::find($target)) && $rec->batch_code
                ? DestructionRecord::where('batch_code', $rec->batch_code)->get()
                : ($rec ? collect([$rec]) : collect())
            : DestructionRecord::where('batch_code', $target)->get();

        if ($records->isEmpty()) return;

        $batchCode = $records->first()->batch_code ?: "ID #{$target}";
        $count = $records->count();
        $action = $count > 1
            ? "Persetujuan Usulan Pemusnahan Arsip Kolektif ({$batchCode})"
            : "Persetujuan Usulan Pemusnahan Arsip: " . ($records->first()->document?->number ?: $batchCode);

        foreach ($records as $record) {
            $record->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
            ]);
        }

        AuditLog::record(
            action: $action,
            documentId: $records->first()->document_id,
            metadata: [
                'batch_code' => $batchCode,
                'count' => $count,
                'approver_id' => Auth::id(),
            ]
        );

        session()->flash('success', 'Usulan berhasil disetujui.');
        $this->dispatch('notify', ['message' => "Usulan pemusnahan berkas {$batchCode} berhasil disetujui.", 'type' => 'success']);
    }

    public function openRejectModal($target)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->hasModuleAccess('approve_destruction')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menolak usulan pemusnahan arsip.', 'type' => 'error']);
            return;
        }

        $records = is_numeric($target)
            ? ($rec = DestructionRecord::find($target)) && $rec->batch_code
                ? DestructionRecord::where('batch_code', $rec->batch_code)->get()
                : ($rec ? collect([$rec]) : collect())
            : DestructionRecord::where('batch_code', $target)->get();

        if ($records->isEmpty()) return;

        $this->rejectBatchCode = $records->first()->batch_code ?: (string) $target;
        $this->rejectRecordId = $records->first()->id;
        $this->rejectRecordDocNumber = $records->count() > 1
            ? "{$records->first()->batch_code} ({$records->count()} Naskah)"
            : ($records->first()->document?->number ?: 'Tanpa Nomor');
        $this->rejectionReason = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-reject');
    }

    public function rejectProposal($target = null)
    {
        $identifier = $target ?? $this->rejectBatchCode ?? $this->rejectRecordId;
        if (!$identifier) return;

        if (!Auth::user()->isSuperAdmin() && !Auth::user()->hasModuleAccess('approve_destruction')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menolak usulan pemusnahan arsip.', 'type' => 'error']);
            return;
        }

        $records = is_numeric($identifier)
            ? ($rec = DestructionRecord::find($identifier)) && $rec->batch_code
                ? DestructionRecord::where('batch_code', $rec->batch_code)->get()
                : ($rec ? collect([$rec]) : collect())
            : DestructionRecord::where('batch_code', $identifier)->get();

        if ($records->isEmpty()) return;

        $batchCode = $records->first()->batch_code ?: "ID #{$identifier}";
        $count = $records->count();
        $action = $count > 1
            ? "Penolakan Usulan Pemusnahan Arsip Kolektif ({$batchCode})"
            : "Penolakan Usulan Pemusnahan Arsip: " . ($records->first()->document?->number ?: $batchCode);

        foreach ($records as $record) {
            $record->update([
                'status' => 'rejected',
                'approver_id' => Auth::id(),
            ]);
        }

        AuditLog::record(
            action: $action,
            documentId: $records->first()->document_id,
            metadata: [
                'batch_code' => $batchCode,
                'count' => $count,
                'approver_id' => Auth::id(),
                'reason' => $this->rejectionReason ?: 'Ditolak oleh pemeriksa',
            ]
        );

        session()->flash('success', 'Usulan berhasil ditolak.');
        $this->dispatch('notify', ['message' => "Usulan pemusnahan berkas {$batchCode} telah ditolak.", 'type' => 'info']);
        $this->dispatch('close-modal', 'modal-reject');
        $this->reset(['rejectBatchCode', 'rejectRecordId', 'rejectRecordDocNumber', 'rejectionReason']);
    }

    public function openExecuteModal($target)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && !Auth::user()->hasModuleAccess('destruction')) {
            $this->dispatch('notify', ['message' => 'Hanya Tapersip atau pengguna dengan hak akses pemusnahan yang berwenang mengeksekusi.', 'type' => 'error']);
            return;
        }

        $records = is_numeric($target)
            ? ($rec = DestructionRecord::find($target)) && $rec->batch_code
                ? DestructionRecord::where('batch_code', $rec->batch_code)->get()
                : ($rec ? collect([$rec]) : collect())
            : DestructionRecord::where('batch_code', $target)->get();

        if ($records->isEmpty()) return;

        $this->activeBatchCode = $records->first()->batch_code ?: (string) $target;
        $this->activeRecordId = $records->first()->id;
        $this->activeRecordDocNumber = $records->count() > 1
            ? "{$records->first()->batch_code} ({$records->count()} Naskah)"
            : ($records->first()->document?->number ?: 'Tanpa Nomor');
        $this->activeRecordDocSubject = $records->count() > 1
            ? "Daftar Naskah: " . $records->pluck('document.number')->filter()->take(3)->implode(', ') . ($records->count() > 3 ? '...' : '')
            : ($records->first()->document?->subject ?: '-');
        $this->baFile = null;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-execute');
    }

    public function executeDestruction()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isTapersip() && !Auth::user()->hasModuleAccess('destruction')) {
            $this->dispatch('notify', ['message' => 'Hanya Tapersip atau pengguna dengan hak akses pemusnahan yang berwenang mengeksekusi.', 'type' => 'error']);
            return;
        }

        $this->validate([
            'baFile' => 'required|mimes:pdf|max:10240',
        ], [
            'baFile.required' => 'Wajib mengunggah file Berita Acara (BA) pemusnahan.',
            'baFile.mimes' => 'Berkas Berita Acara harus berupa dokumen PDF (.pdf).',
            'baFile.max' => 'Ukuran berkas Berita Acara maksimal 10 MB.',
        ]);

        $identifier = $this->activeBatchCode ?? $this->activeRecordId;
        if (!$identifier) return;

        $records = is_numeric($identifier)
            ? ($rec = DestructionRecord::find($identifier)) && $rec->batch_code
                ? DestructionRecord::where('batch_code', $rec->batch_code)->get()
                : ($rec ? collect([$rec]) : collect())
            : DestructionRecord::where('batch_code', $identifier)->get();

        if ($records->isEmpty()) return;

        if ($records->contains(fn($r) => $r->status !== 'approved')) {
            $this->dispatch('notify', ['message' => 'Hanya usulan pemusnahan yang telah disetujui resmi yang dapat dieksekusi.', 'type' => 'error']);
            return;
        }

        $path = $this->baFile->store('ba_files', 'public');
        $now = now();
        $batchCode = $records->first()->batch_code ?: "ID #{$identifier}";
        $count = $records->count();
        $action = $count > 1
            ? "Eksekusi Pemusnahan Arsip & Upload Berita Acara Kolektif ({$batchCode})"
            : "Eksekusi Pemusnahan Arsip & Upload Berita Acara: " . ($records->first()->document?->number ?: $batchCode);

        foreach ($records as $record) {
            $record->update([
                'status' => 'executed',
                'executor_id' => Auth::id(),
                'executed_at' => $now,
                'ba_file_path' => $path,
            ]);

            if ($record->document) {
                $record->document->update([
                    'archive_status' => 'disposed',
                ]);
            }
        }

        AuditLog::record(
            action: $action,
            documentId: $records->first()->document_id,
            metadata: [
                'batch_code' => $batchCode,
                'count' => $count,
                'executor_id' => Auth::id(),
                'ba_file_path' => $path,
                'executed_at' => $now->toDateTimeString(),
            ]
        );

        session()->flash('success', 'Pemusnahan berhasil dieksekusi.');
        $this->dispatch('notify', ['message' => "Pemusnahan berkas {$batchCode} ({$records->count()} arsip) berhasil dieksekusi.", 'type' => 'success']);
        $this->dispatch('close-modal', 'modal-execute');
        $this->reset(['activeBatchCode', 'activeRecordId', 'activeRecordDocNumber', 'activeRecordDocSubject', 'baFile']);
    }

    public function render()
    {
        $expiredCount = Document::whereDate('retention_end_date', '<=', now())
            ->where('archive_status', '!=', 'disposed')
            ->whereDoesntHave('destructionRecords', function($q) {
                $q->whereIn('status', ['proposed', 'approved', 'executed']);
            })
            ->count();

        $proposalsCount = DestructionRecord::distinct('batch_code')->count('batch_code');

        $canApproveDestruction = Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->hasModuleAccess('approve_destruction'));
        $canExecuteDestruction = Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->isTapersip() || Auth::user()->hasModuleAccess('destruction'));

        return view('livewire.archive.destruction-index', [
            'expiredDocuments' => $this->expiredDocuments,
            'proposals' => $this->proposals,
            'expiredCount' => $expiredCount,
            'proposalsCount' => $proposalsCount,
            'isTapersipOrSuper' => Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->isTapersip()),
            'canApproveDestruction' => $canApproveDestruction,
            'canExecuteDestruction' => $canExecuteDestruction,
        ]);
    }
}
