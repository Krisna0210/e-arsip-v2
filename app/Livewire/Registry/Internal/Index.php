<?php

namespace App\Livewire\Registry\Internal;

use App\Models\Document;
use App\Models\Folder;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\DocumentVersion;

class Index extends Component
{
    use WithPagination;

    public $activeFolderId = null;
    public $statusTab = 'all';
    public $search = '';
    
    // Filters
    public $filterSifat = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';
    public $filterUnit = '';

    // Modal Folder
    public $folderName = '';
    public $folderParentId = null;
    public $folderDescription = '';

    // Modal Pindah Loker
    public $moveDocId = null;
    public $moveDocNumber = '';
    public $moveDocSubject = '';
    public $moveCurrentFolderName = '';
    public $moveTargetFolderId = null;

    protected $queryString = [
        'activeFolderId' => ['except' => null],
        'search' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

public function updatedActiveFolderId()
    {
        $this->resetPage();
    }

    public function setActiveFolder($id)
    {
        $this->activeFolderId = $id;
        $this->resetPage();
    }

    public function createFolder()
    {
        $this->reset(['folderName', 'folderParentId', 'folderDescription']);
        $this->dispatch('open-folder-modal');
    }

    public function saveFolder()
    {
        $this->validate([
            'folderName' => 'required|string|max:255',
            'folderParentId' => 'nullable|exists:folders,id',
            'folderDescription' => 'nullable|string',
        ]);

        Folder::create([
            'name' => $this->folderName,
            'type' => 'internal',
            'parent_id' => $this->folderParentId,
            'description' => $this->folderDescription,
        ]);

        $this->reset(['folderName', 'folderParentId', 'folderDescription']);
        $this->dispatch('close-folder-modal');
        $this->dispatch('notify', ['message' => 'Loker berhasil dibuat', 'type' => 'success']);
    }

    public function deleteFolder($id)
    {
        $folder = Folder::forType('internal')->withCount('documents', 'children')->findOrFail($id);
        
        if ($folder->documents_count > 0 || $folder->children_count > 0) {
            $this->dispatch('notify', ['message' => 'Loker tidak kosong', 'type' => 'error']);
            return;
        }

        $folder->delete();
        
        if ($this->activeFolderId == $id) {
            $this->activeFolderId = null;
        }

        $this->dispatch('notify', ['message' => 'Loker berhasil dihapus', 'type' => 'success']);
    }

    public function openMoveModal($docId)
    {
        $doc = Document::with('folder')->findOrFail($docId);

        if (!Auth::user()->hasDocumentPermission('archive', $doc->sifat ?? 'biasa')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk memindahkan loker dokumen ini.', 'type' => 'error']);
            return;
        }

        $this->moveDocId = $doc->id;
        $this->moveDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->moveDocSubject = $doc->subject;
        $this->moveCurrentFolderName = $doc->folder ? $doc->folder->name : 'Tanpa Loker / Root';
        $this->moveTargetFolderId = $doc->folder_id;

        $this->dispatch('open-modal', 'modal-move-document');
    }

    public function executeMoveDocument()
    {
        if (!$this->moveDocId) return;

        $doc = Document::with(['currentVersion', 'folder'])->findOrFail($this->moveDocId);

        if (!Auth::user()->hasDocumentPermission('archive', $doc->sifat ?? 'biasa')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk memindahkan dokumen ini.');
        }

        $doc->relocateFolder($this->moveTargetFolderId ?: null);

        $this->reset(['moveDocId', 'moveDocNumber', 'moveDocSubject', 'moveCurrentFolderName', 'moveTargetFolderId']);
        $this->dispatch('close-modal', 'modal-move-document');
        $this->dispatch('notify', ['message' => 'Loker dan berkas fisik dokumen berhasil dipindahkan.', 'type' => 'success']);
    }

    public function render()
    {
        // Recursive loading of children for internal
        $folders = Folder::forType('internal')
            ->whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->forType('internal')->with(['children' => function($q2) {
                    $q2->forType('internal');
                }]);
            }])
            ->get();
            
        $allFolders = Folder::forType('internal')->with('parent')->orderBy('name')->get();

        $query = Document::with(['folder', 'documentType', 'classification', 'unit', 'recipients.recipient', 'currentVersion'])
            ->where('type', 'internal');

        if ($this->activeFolderId) {
            $query->where('folder_id', $this->activeFolderId);
        }

        if ($this->statusTab !== 'all') {
            $query->where('status', $this->statusTab);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('recipient_name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('unit', function ($qu) {
                      $qu->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('recipients', function ($qr) {
                      $qr->whereHasMorph('recipient', [\App\Models\Unit::class], function ($qu) {
                          $qu->where('name', 'like', '%' . $this->search . '%');
                      });
                  });
            });
        }

        if ($this->filterSifat) {
            $query->where('sifat', $this->filterSifat);
        }

        if ($this->filterDateStart) {
            $query->whereDate('document_date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->whereDate('document_date', '<=', $this->filterDateEnd);
        }

        if ($this->filterUnit) {
            $query->where('unit_id', $this->filterUnit);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.registry.internal.index', [
            'folders' => $folders,
            'allFolders' => $allFolders,
            'documents' => $documents,
        ]);
    }
}




