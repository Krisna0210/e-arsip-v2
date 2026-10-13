<?php

namespace App\Livewire\Registry\Incoming;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Unit;
use App\Models\Approval;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Document $document;

    public $number;
    public $document_date;
    public $received_date;
    public $sender;
    public $subject;
    public $unit_id;
    public $folder_id;
    public $sifat;
    public $file;

    // Rejection details
    public $rejectionReason;
    public $rejectorName;
    public $rejectedAt;

    public $units;
    public $folders;

    public function mount(Document $document)
    {
        if ($document->created_by !== Auth::id() || $document->status !== 'rejected') {
            abort(403, 'Unauthorized action.');
        }

        $this->document = $document;
        $this->number = $document->number;
        $this->document_date = $document->document_date ? $document->document_date->format('Y-m-d') : null;
        $this->received_date = $document->received_date ? $document->received_date->format('Y-m-d') : null;
        $this->sender = $document->sender;
        $this->subject = $document->subject;
        $this->unit_id = $document->unit_id;
        $this->folder_id = $document->folder_id;
        $this->sifat = $document->sifat;

        $this->units = Unit::all();
        $this->folders = \App\Models\Folder::forType('incoming')->get();

        $approval = Approval::with('approver')
            ->where('document_id', $document->id)
            ->where('status', 'rejected')
            ->latest('acted_at')
            ->first();

        if ($approval) {
            $this->rejectionReason = $approval->reason;
            $this->rejectorName = $approval->approver ? $approval->approver->name : 'Unknown';
            $this->rejectedAt = $approval->acted_at ? $approval->acted_at->format('d M Y H:i') : null;
        }
    }

    protected function rules()
    {
        return [
            'number' => 'nullable|string|max:255',
            'document_date' => 'required|date|before_or_equal:today',
            'received_date' => 'required|date|after_or_equal:document_date',
            'sender' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'folder_id' => 'nullable|exists:folders,id',
            'unit_id' => 'required|exists:units,id',
            'sifat' => 'required|in:biasa,penting,rahasia',
            'file' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    public function resubmit()
    {
        $this->validate();

        $this->document->update([
            'number' => $this->number,
            'document_date' => $this->document_date,
            'received_date' => $this->received_date,
            'sender' => $this->sender,
            'subject' => $this->subject,
            'unit_id' => $this->unit_id,
            'folder_id' => $this->folder_id,
            'sifat' => $this->sifat,
            'status' => 'pending_verification',
        ]);

        if ($this->file) {
            $latestVersion = DocumentVersion::where('document_id', $this->document->id)
                ->max('version_number');
            $newVersionNumber = $latestVersion ? $latestVersion + 1 : 2;

            $fileName = $this->document->generateArchiveFileName('pdf', (string) $newVersionNumber);
            $path = $this->file->storeAs('documents', $fileName, 'public');

            $version = DocumentVersion::create([
                'document_id' => $this->document->id,
                'version_number' => $newVersionNumber,
                'file_path' => $path,
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            $this->document->update([
                'current_version_id' => $version->id,
            ]);
        }

        // Start Workflow (Verification Step Only for Incoming)
        $workflow = \App\Models\WorkflowInstance::create([
            'document_id' => $this->document->id,
            'workflow_type' => 'surat_masuk_verification',
            'status' => 'active',
            'current_step_sequence' => 1,
        ]);

        // Step 1: Verification
        $verifierId = \App\Models\User::whereHas('role', function($q) { $q->where('name', 'Superadmin'); })->first()->id ?? Auth::id();
        \App\Models\WorkflowStep::create([
            'workflow_instance_id' => $workflow->id,
            'step_type' => 'verification',
            'sequence' => 1,
            'status' => 'active',
            'assignee_type' => \App\Models\User::class,
            'assignee_id' => $verifierId,
            'started_at' => now(),
        ]);

        session()->flash('success', 'Surat berhasil diajukan ulang untuk verifikasi.');
        return redirect()->route('registry.incoming.index');
    }

    public function render()
    {
        return view('livewire.registry.incoming.edit');
    }
}
