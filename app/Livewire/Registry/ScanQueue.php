<?php

namespace App\Livewire\Registry;

use App\Models\Document;
use App\Models\DocumentVersion;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class ScanQueue extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterType = 'all'; // all, outgoing, internal

    public $uploadDocId = null;
    public $uploadDocNumber = '';
    public $uploadDocSubject = '';
    public $uploadDocDate = '';
    public $uploadDocType = '';
    public $uploadDocCreator = '';
    public $uploadFolderId = null;
    public $uploadDocTypeRaw = '';
    public $finalFile = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function openUploadModal($id)
    {
        $doc = Document::with('createdBy')->findOrFail($id);
        
        // Cek permission archive (termasuk superadmin check)
        if (!Auth::user()->hasDocumentPermission('archive', $doc->sifat ?? 'biasa')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk mengarsipkan dokumen ini.', 'type' => 'error']);
            return;
        }

        $this->uploadDocId = $doc->id;
        $this->uploadDocNumber = $doc->number;
        $this->uploadDocSubject = $doc->subject;
        $this->uploadDocDate = $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->format('d M Y') : '-';
        $this->uploadDocType = $doc->type === 'outgoing' ? 'Surat Keluar' : 'Memo Internal';
        $this->uploadDocTypeRaw = $doc->type;
        $this->uploadFolderId = $doc->folder_id;
        $this->uploadDocCreator = $doc->createdBy ? $doc->createdBy->name : '-';
        
        $this->dispatch('open-modal', 'modal-upload-queue');
    }

    public function saveFinalFile()
    {
        $this->validate([
            'finalFile' => 'required|file|mimes:pdf|max:10240',
            'uploadFolderId' => 'nullable|exists:folders,id',
        ]);

        $document = Document::findOrFail($this->uploadDocId);

        // Security check backend
        if (!Auth::user()->hasDocumentPermission('archive', $document->sifat ?? 'biasa')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Tetapkan loker terlebih dahulu agar struktur direktori di NAS sesuai
        $document->folder_id = $this->uploadFolderId;
        $document->load('folder.parent');

        $directory = $document->getStorageDirectoryPath();
        $fileName = $document->generateArchiveFileName('pdf', '1.0');
        $path = $this->finalFile->storeAs($directory, $fileName, 'nas');

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => '1.0',
            'file_path' => $path,
            'created_by' => Auth::id(),
            'status' => 'active',
        ]);

        $document->update([
            'current_version_id' => $version->id,
            'status' => 'archived',
            'folder_id' => $this->uploadFolderId,
        ]);

        $this->reset(['uploadDocId', 'finalFile', 'uploadDocNumber', 'uploadDocSubject', 'uploadDocDate', 'uploadDocType', 'uploadDocTypeRaw', 'uploadFolderId', 'uploadDocCreator']);
        $this->dispatch('close-modal', 'modal-upload-queue');
        $this->dispatch('notify', ['message' => 'File PDF Final berhasil diunggah dan diarsipkan.', 'type' => 'success']);
    }

    public function render()
    {
        $query = Document::with(['documentType', 'classification', 'unit', 'createdBy'])
            ->where('status', 'waiting_for_file')
            ->whereIn('type', ['outgoing', 'internal']);

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhereHas('createdBy', function($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $documents = $query->orderBy('created_at', 'asc')->paginate(10); // asc: yang paling lama antre didahulukan

        $availableFolders = $this->uploadDocTypeRaw
            ? \App\Models\Folder::forType($this->uploadDocTypeRaw)->orderBy('name')->get()
            : collect();

        return view('livewire.registry.scan-queue', [
            'documents' => $documents,
            'availableFolders' => $availableFolders,
        ])->layout('components.layouts.app', ['title' => 'Antrean Scan Tapersip']);
    }
}


