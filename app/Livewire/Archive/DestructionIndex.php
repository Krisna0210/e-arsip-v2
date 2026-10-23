<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Document;
use App\Models\DestructionRecord;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DestructionIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $activeTab = 'expired';
    public $selectedDocId = null;
    public $proposalReason = '';
    public $baFile = null;
    public $activeRecordId = null;

    public function getExpiredDocumentsProperty()
    {
        return Document::with(['documentType', 'classification', 'unit', 'folder'])
            ->whereDate('retention_end_date', '<=', now())
            ->where('archive_status', '!=', 'disposed')
            ->whereDoesntHave('destructionRecords', function($q) {
                $q->whereIn('status', ['proposed', 'approved', 'executed']);
            })
            ->paginate(10);
    }

    public function getProposalsProperty()
    {
        return DestructionRecord::with(['document.documentType', 'proposer', 'approver', 'executor'])
            ->latest()
            ->paginate(10);
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openProposeModal($docId)
    {
        $this->selectedDocId = $docId;
        $this->proposalReason = '';
        $this->dispatch('open-modal', 'modal-propose');
    }

    public function submitProposal()
    {
        $this->validate([
            'proposalReason' => 'required|string|max:500',
        ]);

        $record = DestructionRecord::create([
            'document_id' => $this->selectedDocId,
            'proposer_id' => Auth::id(),
            'status' => 'proposed',
            'reason' => $this->proposalReason,
        ]);

        $document = Document::find($this->selectedDocId);
        AuditLog::record(
            action: "Pengajuan Usulan Pemusnahan Arsip: " . ($document?->number ?: 'Dokumen'),
            documentId: $this->selectedDocId,
            metadata: [
                'record_id' => $record->id,
                'reason' => $this->proposalReason,
                'proposer_id' => Auth::id(),
            ]
        );

        session()->flash('success', 'Usulan pemusnahan berhasil diajukan.');
        $this->dispatch('notify', ['message' => 'Usulan pemusnahan berhasil diajukan.', 'type' => 'success']);
        $this->dispatch('close-modal', 'modal-propose');
        $this->reset(['selectedDocId', 'proposalReason']);
    }

    public function approveProposal($id)
    {
        $record = DestructionRecord::with('document')->findOrFail($id);
        $record->update([
            'status' => 'approved',
            'approver_id' => Auth::id(),
        ]);

        AuditLog::record(
            action: "Persetujuan Usulan Pemusnahan Arsip: " . ($record->document?->number ?: 'Dokumen'),
            documentId: $record->document_id,
            metadata: [
                'record_id' => $record->id,
                'approver_id' => Auth::id(),
            ]
        );
        
        session()->flash('success', 'Usulan berhasil disetujui.');
        $this->dispatch('notify', ['message' => 'Usulan pemusnahan berhasil disetujui.', 'type' => 'success']);
    }

    public function rejectProposal($id)
    {
        $record = DestructionRecord::with('document')->findOrFail($id);
        $record->update([
            'status' => 'rejected',
            'approver_id' => Auth::id(),
        ]);

        AuditLog::record(
            action: "Penolakan Usulan Pemusnahan Arsip: " . ($record->document?->number ?: 'Dokumen'),
            documentId: $record->document_id,
            metadata: [
                'record_id' => $record->id,
                'approver_id' => Auth::id(),
            ]
        );
        
        session()->flash('success', 'Usulan berhasil ditolak.');
        $this->dispatch('notify', ['message' => 'Usulan pemusnahan telah ditolak.', 'type' => 'info']);
    }

    public function openExecuteModal($id)
    {
        $this->activeRecordId = $id;
        $this->baFile = null;
        $this->dispatch('open-modal', 'modal-execute');
    }

    public function executeDestruction()
    {
        $this->validate([
            'baFile' => 'required|mimes:pdf|max:10240',
        ]);

        $record = DestructionRecord::with('document')->findOrFail($this->activeRecordId);
        
        $path = $this->baFile->store('ba_files', 'public');

        $record->update([
            'status' => 'executed',
            'executor_id' => Auth::id(),
            'executed_at' => now(),
            'ba_file_path' => $path,
        ]);

        $document = $record->document;
        $document->update([
            'archive_status' => 'disposed',
        ]);

        AuditLog::record(
            action: "Eksekusi Pemusnahan Arsip & Upload Berita Acara: " . ($document?->number ?: 'Dokumen'),
            documentId: $document->id,
            metadata: [
                'record_id' => $record->id,
                'executor_id' => Auth::id(),
                'ba_file_path' => $path,
                'executed_at' => now()->toDateTimeString(),
            ]
        );

        session()->flash('success', 'Pemusnahan berhasil dieksekusi.');
        $this->dispatch('notify', ['message' => 'Pemusnahan berkas fisik berhasil dieksekusi.', 'type' => 'success']);
        $this->dispatch('close-modal', 'modal-execute');
        $this->reset(['activeRecordId', 'baFile']);
    }

    public function render()
    {
        return view('livewire.archive.destruction-index', [
            'expiredDocuments' => $this->expiredDocuments,
            'proposals' => $this->proposals,
        ]);
    }
}