<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Document;
use App\Models\DestructionRecord;
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
        return Document::whereDate('retention_end_date', '<=', now())
            ->where('archive_status', '!=', 'disposed')
            ->whereDoesntHave('destructionRecords', function($q) {
                $q->whereIn('status', ['proposed', 'approved', 'executed']);
            })
            ->paginate(10);
    }

    public function getProposalsProperty()
    {
        return DestructionRecord::with(['document', 'proposer', 'approver', 'executor'])
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

        DestructionRecord::create([
            'document_id' => $this->selectedDocId,
            'proposer_id' => Auth::id(),
            'status' => 'proposed',
            'reason' => $this->proposalReason,
        ]);

        session()->flash('success', 'Usulan pemusnahan berhasil diajukan.');
        $this->dispatch('close-modal', 'modal-propose');
        $this->reset(['selectedDocId', 'proposalReason']);
    }

    public function approveProposal($id)
    {
        $record = DestructionRecord::findOrFail($id);
        $record->update([
            'status' => 'approved',
            'approver_id' => Auth::id(),
        ]);
        
        session()->flash('success', 'Usulan berhasil disetujui.');
    }

    public function rejectProposal($id)
    {
        $record = DestructionRecord::findOrFail($id);
        $record->update([
            'status' => 'rejected',
            'approver_id' => Auth::id(),
        ]);
        
        session()->flash('success', 'Usulan berhasil ditolak.');
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

        $record = DestructionRecord::findOrFail($this->activeRecordId);
        
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

        session()->flash('success', 'Pemusnahan berhasil dieksekusi.');
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