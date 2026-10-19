<?php

namespace App\Livewire\Registry\Incoming;

use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\DocumentVersion;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $number;
    public $document_date;
    public $received_date;
    public $sender;
    public $subject;
    public $unit_id;
    public $classification_id;
    public $folder_id;
    public $sifat = 'biasa';
    public $file;
    public $showSuccessModal = false;

    // Data ringkasan setelah berhasil diarsipkan
    public $savedDocumentNumber = '';
    public $savedDocumentSubject = '';
    public $savedDocumentSender = '';
    public $savedDocumentUnitName = '';
    public $savedDocumentFolderName = '';

    public $units;
    public $folders;
    public $classifications;

    public function mount()
    {
        $this->document_date = date('Y-m-d');
        $this->received_date = date('Y-m-d');

        $this->classifications = Classification::whereNotNull('parent_id')->get();
        if ($this->classifications->isEmpty()) {
            $this->classifications = Classification::all();
        }

        $this->units = Unit::with('parent')->where('status', 'active')->orderBy('type')->orderBy('name')->get();
        $this->folders = \App\Models\Folder::forType('incoming')->get();
    }

    protected function baseRules($isStrict = false)
    {
        return [
            'number' => $isStrict ? 'required|string|max:255' : 'nullable|string|max:255',
            'document_date' => 'required|date|before_or_equal:today',
            'received_date' => 'required|date|after_or_equal:document_date',
            'sender' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'classification_id' => 'required|exists:classifications,id',
            'folder_id' => 'nullable|exists:folders,id',
            'unit_id' => 'required|exists:units,id',
            'sifat' => 'required|in:biasa,penting,rahasia',
            'file' => $isStrict ? 'required|file|mimes:pdf|max:10240' : 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    public function save()
    {
        $this->validate($this->baseRules(true));

        $document = $this->createDocument('archived');

        $this->handleFileAndVersion($document);

        \App\Models\AuditLog::record(
            action: 'Registrasi Surat Masuk: ' . ($this->number ?: $this->subject),
            documentId: $document->id,
            metadata: [
                'subject' => $this->subject,
                'type' => 'incoming',
                'sender' => $this->sender,
            ]
        );

        $document->load(['unit', 'folder']);
        $this->savedDocumentNumber = $document->number ?: 'Tanpa Nomor';
        $this->savedDocumentSubject = $document->subject;
        $this->savedDocumentSender = $document->sender;
        $this->savedDocumentUnitName = $document->unit ? $document->unit->name : '-';
        $this->savedDocumentFolderName = $document->folder ? $document->folder->name : 'Tanpa Loker / Root';
        $this->showSuccessModal = true;
    }

    public function resetFormAndInputAgain()
    {
        $this->reset([
            'number',
            'sender',
            'subject',
            'unit_id',
            'classification_id',
            'folder_id',
            'file',
            'showSuccessModal',
            'savedDocumentNumber',
            'savedDocumentSubject',
            'savedDocumentSender',
            'savedDocumentUnitName',
            'savedDocumentFolderName',
        ]);

        $this->document_date = date('Y-m-d');
        $this->received_date = date('Y-m-d');
        $this->sifat = 'biasa';
    }

    public function redirectToIndex()
    {
        return redirect()->route('registry.incoming.index');
    }

    public function finishAndRedirect()
    {
        return redirect()->route('dashboard');
    }

    protected function createDocument($status)
    {
        $classification = Classification::find($this->classification_id);
        $retentionStartDate = null;
        $retentionEndDate = null;
        if ($classification && $classification->active_years > 0) {
            $retentionStartDate = $this->document_date ?: date('Y-m-d');
            $retentionEndDate = date('Y-m-d', strtotime("+{$classification->active_years} years", strtotime($retentionStartDate)));
        }

        return Document::create([
            'type' => 'incoming',
            'number' => $this->number,
            'document_date' => $this->document_date,
            'received_date' => $this->received_date,
            'sender' => $this->sender,
            'subject' => $this->subject,
            'unit_id' => $this->unit_id,
            'classification_id' => $this->classification_id,
            'folder_id' => $this->folder_id,
            'sifat' => $this->sifat,
            'status' => $status,
            'retention_start_date' => $retentionStartDate,
            'retention_end_date' => $retentionEndDate,
            'created_by' => Auth::id(),
        ]);
    }

    protected function handleFileAndVersion(Document $document)
    {
        if ($this->file) {
            $document->load('folder.parent');
            $directory = $document->getStorageDirectoryPath();
            $fileName = $document->generateArchiveFileName('pdf', '1.0');
            $path = $this->file->storeAs($directory, $fileName, 'nas');

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_path' => $path,
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            $document->update([
                'current_version_id' => $version->id,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.registry.incoming.create');
    }
}


