<?php

namespace App\Livewire\Registry\Outgoing;

use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $number;
    public $document_date;
    public $unit_id; // Sender unit
    public $recipient_name;
    public $subject;
    public $classification_id;
    public $document_type_id;
    public $folder_id;
    public $sifat = 'biasa';
    public $file;
    public $showSuccessModal = false;

    public $classifications;
    public $documentTypes;
    public $units;
    public $folders;
    public function mount()
    {
        $this->classifications = Classification::whereNotNull('parent_id')->get();
        $this->documentTypes = \App\Models\DocumentType::all();
        $this->units = Unit::all();
        $this->folders = \App\Models\Folder::forType('outgoing')->get();
        }

    protected function baseRules($isStrict = false)
    {
        return [
            'document_date' => 'required|date|before_or_equal:today',
            'unit_id' => 'required|exists:units,id',
            'recipient_name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'classification_id' => 'required|exists:classifications,id',
            'folder_id' => 'nullable|exists:folders,id',
            'sifat' => 'required|in:biasa,penting,rahasia',
            'file' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    public function getCanArchiveProperty(): bool
    {
        return Auth::user()->hasDocumentPermission('archive', $this->sifat ?? 'biasa');
    }

    protected function generateNumber(): string
    {
        $year = date('Y');
        
        $docTypeRecord = \App\Models\DocumentType::find($this->document_type_id);
        $format = $docTypeRecord && $docTypeRecord->numbering_format ? $docTypeRecord->numbering_format : '{NO}/{CLASS}/{UNIT}/{YYYY}';
        
        $countThisYear = \App\Models\Document::where('document_type_id', $this->document_type_id)
            ->whereYear('document_date', $year)
            ->count() + 1;
        
        $sequenceNo = str_pad($countThisYear, 3, '0', STR_PAD_LEFT);
        $classification = \App\Models\Classification::find($this->classification_id);
        $classCode = $classification ? $classification->code : 'UM';
        $unit = \App\Models\Unit::find($this->unit_id);
        $unitCode = $unit ? $unit->code : 'ORG';
        $month = date('m');
        
        return str_replace(
            ['{NO}', '{CLASS}', '{kodeklasi}', '{UNIT}', '{YYYY}', '{MM}'],
            [$sequenceNo, $classCode, $classCode, $unitCode, $year, $month],
            $format
        );
    }

    public function bookNumber()
    {
        $this->validate($this->baseRules(false));

        $this->number = $this->generateNumber();
        $this->file = null; // Do not attach file for booking

        $document = $this->createDocument('waiting_for_file');

        $this->showSuccessModal = true;
    }

    public function saveAndArchive()
    {
        // Server-side security check: must have archive permission for this sifat
        if (!Auth::user()->hasDocumentPermission('archive', $this->sifat ?? 'biasa')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengarsipkan dokumen secara langsung.');
        }

        $rules = $this->baseRules(false);
        $rules['file'] = 'required|file|mimes:pdf|max:10240';
        $this->validate($rules);

        $this->number = $this->generateNumber();

        $document = $this->createDocument('archived');
        $this->handleFileAndVersion($document);

        session()->flash('success', "Dokumen Surat Keluar berhasil disimpan dan diarsipkan dengan nomor: {$this->number}");
        return redirect()->route('registry.outgoing.index');
    }

    public function save()
    {
        return $this->bookNumber();
    }

    public function finishAndRedirect()
    {
        return redirect()->route('dashboard');
    }

    protected function createDocument($status)
    {
        return Document::create([
            'document_type_id' => $this->document_type_id,
            'type' => 'outgoing',
            'number' => $this->number,
            'document_date' => $this->document_date,
            'unit_id' => $this->unit_id,
            'recipient_name' => $this->recipient_name,
            'subject' => $this->subject,
            'classification_id' => $this->classification_id,
            'folder_id' => $this->folder_id,
            'sifat' => $this->sifat,
'status' => $status,
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
        return view('livewire.registry.outgoing.create');
    }
}













