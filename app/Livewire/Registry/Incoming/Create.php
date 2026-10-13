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
    public $folder_id;
    public $sifat = 'biasa';
    public $file;

    public $units;
    public $folders;

    public function mount()
    {
        $this->units = Unit::all();
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

        session()->flash('success', 'Dokumen berhasil disimpan ke arsip.');
        return redirect()->route('registry.incoming.index');
    }

    protected function createDocument($status)
    {
        return Document::create([
            'type' => 'incoming',
            'number' => $this->number,
            'document_date' => $this->document_date,
            'received_date' => $this->received_date,
            'sender' => $this->sender,
            'subject' => $this->subject,
            'unit_id' => $this->unit_id,
            'folder_id' => $this->folder_id,
            'sifat' => $this->sifat,
            'status' => $status,
            'created_by' => Auth::id(),
        ]);
    }

    protected function handleFileAndVersion(Document $document)
    {
        if ($this->file) {
            $fileName = $document->generateArchiveFileName('pdf', '1.0');
            $path = $this->file->storeAs('documents', $fileName, 'public');

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


