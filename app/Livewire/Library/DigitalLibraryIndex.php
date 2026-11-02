<?php

namespace App\Livewire\Library;

use App\Models\AuditLog;
use App\Models\DigitalDocument;
use App\Models\DigitalDocumentAccessRequest;
use App\Models\DigitalFolder;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DigitalLibraryIndex extends Component
{
    use WithPagination, WithFileUploads;

    // Active Tab Navigation
    public string $activeTab = 'repository'; // 'repository', 'my_requests', 'pending_approvals'

    // Filter & Search State
    public string $search = '';
    public string $filterCategory = 'all';
    public string $filterYear = '';
    public string $filterUnit = '';
    public ?int $activeFolderId = null;
    public string $selectedTag = '';

    // Upload Modal Form State
    public string $uploadTitle = '';
    public string $uploadCategory = 'As-Built Drawing';
    public string $uploadAccessType = 'public'; // 'public' or 'private'
    public ?int $uploadYear = null;
    public ?int $uploadUnitId = null;
    public string $uploadTags = '';
    public string $uploadDescription = '';
    public ?int $uploadFolderId = null;
    public $uploadFile = null;

    // Folder Modal Form State
    public ?int $editFolderId = null;
    public string $folderName = '';
    public ?int $folderParentId = null;
    public string $folderDescription = '';

    // Preview & Detail State
    public ?int $previewDocId = null;
    public ?DigitalDocument $previewDoc = null;

    // Delete Confirmation State
    public ?int $deleteDocId = null;
    public string $deleteDocTitle = '';

    // Move Document State
    public ?int $moveDocId = null;
    public string $moveDocTitle = '';
    public string $moveDocNumber = '';
    public ?int $moveTargetFolderId = null;

    // Move Folder State
    public ?int $moveFolderId = null;
    public string $moveFolderName = '';
    public ?int $moveTargetParentId = null;

    // Delete Folder State
    public ?int $deleteFolderId = null;
    public string $deleteFolderName = '';
    public int $deleteFolderDocsCount = 0;
    public int $deleteFolderSubCount = 0;

    // Request Access State (Staff)
    public ?int $requestAccessDocId = null;
    public string $requestAccessDocTitle = '';
    public string $requestAccessDocNumber = '';
    public string $requestAccessReason = '';

    // Approval State (Tapersip)
    public ?int $approvalRequestId = null;
    public string $approvalDocTitle = '';
    public string $approvalDocNumber = '';
    public string $approvalUserName = '';
    public string $approvalUserReason = '';
    public int $approvalDurationHours = 24;

    // Rejection State (Tapersip)
    public ?int $rejectionRequestId = null;
    public string $rejectionReasonNotes = '';

    protected $queryString = [
        'activeTab' => ['except' => 'repository'],
        'search' => ['except' => ''],
        'filterCategory' => ['except' => 'all'],
        'filterYear' => ['except' => ''],
        'filterUnit' => ['except' => ''],
        'activeFolderId' => ['except' => null],
        'selectedTag' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->uploadYear = (int) date('Y');
        $this->uploadUnitId = Auth::user()->unit_id ?? null;

        if (request()->has('folder')) {
            $this->activeFolderId = (int) request('folder');
        }

        if (request()->has('tab')) {
            $tab = request('tab');
            if (in_array($tab, ['repository', 'my_requests', 'pending_approvals'])) {
                $this->activeTab = $tab;
            }
        }

        if (request()->has('highlight')) {
            $highlight = request('highlight');
            if (str_starts_with($highlight, 'req-')) {
                $this->activeTab = 'pending_approvals';
            }
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['repository', 'my_requests', 'pending_approvals'])) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterYear(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUnit(): void
    {
        $this->resetPage();
    }

    public function selectFolder(?int $folderId): void
    {
        $this->activeFolderId = $folderId;
        $this->resetPage();
    }

    public function filterByTag(string $tag): void
    {
        $this->selectedTag = trim($tag, '# ');
        $this->resetPage();
    }

    public function clearTagFilter(): void
    {
        $this->selectedTag = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterCategory', 'filterYear', 'filterUnit', 'selectedTag']);
        $this->resetPage();
    }

    public function openUploadModal(?int $folderId = null): void
    {
        if (!$this->canUploadAndCreateFolder) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk mengunggah berkas baru.', 'type' => 'error']);
            return;
        }

        $this->uploadFolderId = $folderId ?? $this->activeFolderId;
        $this->uploadTitle = '';
        $this->uploadCategory = 'As-Built Drawing';
        $this->uploadAccessType = 'public';
        $this->uploadYear = (int) date('Y');
        $this->uploadUnitId = Auth::user()->unit_id ?? null;
        $this->uploadTags = '';
        $this->uploadDescription = '';
        $this->uploadFile = null;

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-upload-digital-doc');
    }

    public function saveDocument(): void
    {
        if (!$this->canUploadAndCreateFolder) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        $this->validate([
            'uploadTitle' => 'required|string|min:3|max:255',
            'uploadCategory' => 'required|in:' . implode(',', DigitalDocument::CATEGORIES),
            'uploadAccessType' => 'required|in:public,private',
            'uploadYear' => 'required|integer|min:1950|max:2099',
            'uploadUnitId' => 'nullable|exists:units,id',
            'uploadTags' => 'nullable|string|max:500',
            'uploadDescription' => 'nullable|string|max:2000',
            'uploadFolderId' => 'nullable|exists:digital_folders,id',
            'uploadFile' => 'required|file|max:51200', // 50MB
        ], [
            'uploadTitle.required' => 'Nama / Judul dokumen wajib diisi.',
            'uploadTitle.min' => 'Nama dokumen minimal 3 karakter.',
            'uploadCategory.required' => 'Kategori berkas wajib dipilih.',
            'uploadAccessType.required' => 'Tipe akses dokumen wajib dipilih.',
            'uploadYear.required' => 'Tahun dokumen wajib diisi.',
            'uploadFile.required' => 'Wajib memilih file berkas digital untuk diunggah.',
            'uploadFile.max' => 'Ukuran file berkas maksimal 50 MB.',
        ]);

        $year = (int) $this->uploadYear;
        $docNumber = DigitalDocument::generateDocumentNumber($year);

        // Sanitize & build destination path for NAS/Storage
        $catSlug = Str::slug($this->uploadCategory);
        $directory = "library/{$catSlug}/{$year}";
        $originalName = $this->uploadFile->getClientOriginalName();
        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        
        $cleanTitle = Str::slug(Str::limit($this->uploadTitle, 40, ''));
        $safeFileName = "{$docNumber}_{$cleanTitle}.{$ext}";

        // Simpan file ke disk 'nas' (Dual-layer portable resolution)
        $path = $this->uploadFile->storeAs($directory, $safeFileName, 'nas');

        $doc = DigitalDocument::create([
            'title' => $this->uploadTitle,
            'document_number' => $docNumber,
            'category' => $this->uploadCategory,
            'access_type' => $this->uploadAccessType,
            'year' => $year,
            'unit_id' => $this->uploadUnitId,
            'tags' => $this->uploadTags,
            'description' => $this->uploadDescription,
            'folder_id' => $this->uploadFolderId,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_size' => $this->uploadFile->getSize(),
            'mime_type' => $this->uploadFile->getMimeType(),
            'created_by' => Auth::id(),
        ]);

        AuditLog::record(
            action: "Unggah Berkas Pustaka Digital: {$docNumber} - {$doc->title} ({$doc->access_type})",
            documentId: null,
            metadata: [
                'document_number' => $docNumber,
                'category' => $doc->category,
                'access_type' => $doc->access_type,
                'year' => $doc->year,
                'file_name' => $originalName,
                'uploader_id' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-upload-digital-doc');
        $this->dispatch('notify', [
            'message' => "Berkas {$docNumber} ({$doc->access_type}) berhasil diunggah ke Pustaka Digital.",
            'type' => 'success'
        ]);

        $this->reset(['uploadTitle', 'uploadAccessType', 'uploadTags', 'uploadDescription', 'uploadFile']);
    }

    public function openCreateFolderModal(?int $parentId = null): void
    {
        if (!$this->canUploadAndCreateFolder) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk membuat folder baru.', 'type' => 'error']);
            return;
        }

        $this->editFolderId = null;
        $this->folderName = '';
        $this->folderParentId = $parentId ?? $this->activeFolderId;
        $this->folderDescription = '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-folder-editor');
    }

    public function saveFolder(): void
    {
        if (!$this->canUploadAndCreateFolder) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        $this->validate([
            'folderName' => 'required|string|min:2|max:100',
            'folderParentId' => 'nullable|exists:digital_folders,id',
            'folderDescription' => 'nullable|string|max:500',
        ], [
            'folderName.required' => 'Nama folder wajib diisi.',
            'folderName.min' => 'Nama folder minimal 2 karakter.',
        ]);

        $folder = DigitalFolder::create([
            'name' => $this->folderName,
            'parent_id' => $this->folderParentId,
            'description' => $this->folderDescription,
            'created_by' => Auth::id(),
        ]);

        $this->dispatch('close-modal', 'modal-folder-editor');
        $this->dispatch('notify', [
            'message' => "Folder '{$folder->name}' berhasil dibuat.",
            'type' => 'success'
        ]);

        $this->reset(['folderName', 'folderDescription', 'folderParentId', 'editFolderId']);
    }

    public function openPreviewModal(int $docId): void
    {
        $doc = DigitalDocument::with(['unit', 'folder', 'creator'])->findOrFail($docId);

        if ($doc->access_type === 'private' && !$doc->hasActiveAccessForUser(Auth::id())) {
            $this->openRequestAccessModal($doc->id);
            return;
        }

        $this->previewDocId = $doc->id;
        $this->previewDoc = $doc;

        $this->dispatch('open-modal', 'modal-preview-digital-doc');
    }

    public function openRequestAccessModal(int $docId): void
    {
        $doc = DigitalDocument::findOrFail($docId);

        if ($doc->hasActiveAccessForUser(Auth::id())) {
            $this->openPreviewModal($doc->id);
            return;
        }

        $pending = $doc->getPendingAccessForUser(Auth::id());
        if ($pending) {
            $this->dispatch('notify', [
                'message' => 'Anda telah mengajukan izin untuk berkas ini dan sedang menunggu persetujuan Tapersip.',
                'type' => 'info'
            ]);
            return;
        }

        $this->requestAccessDocId = $doc->id;
        $this->requestAccessDocTitle = $doc->title;
        $this->requestAccessDocNumber = $doc->document_number;
        $this->requestAccessReason = '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-request-private-access');
    }

    public function submitRequestAccess(): void
    {
        $this->validate([
            'requestAccessReason' => 'required|string|min:5|max:500',
        ], [
            'requestAccessReason.required' => 'Mohon cantumkan alasan pengajuan akses berkas privat.',
            'requestAccessReason.min' => 'Alasan permohonan akses minimal 5 karakter.',
        ]);

        if (!$this->requestAccessDocId) return;

        $doc = DigitalDocument::findOrFail($this->requestAccessDocId);

        $request = DigitalDocumentAccessRequest::create([
            'digital_document_id' => $doc->id,
            'user_id' => Auth::id(),
            'reason' => $this->requestAccessReason,
            'status' => 'pending',
        ]);

        AuditLog::record(
            action: "Pengajuan Izin Akses Berkas Privat: {$doc->document_number} - {$doc->title}",
            documentId: null,
            metadata: [
                'document_id' => $doc->id,
                'request_id' => $request->id,
                'reason' => $this->requestAccessReason,
                'user_id' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-request-private-access');

        $user = Auth::user();
        $unitName = $user?->unit?->name ? " ({$user->unit->name})" : '';
        $this->dispatch('desktop-notify', [
            'title' => 'Permohonan Akses Berkas Privat',
            'body' => "{$user->name}{$unitName} mengajukan izin berkas {$doc->document_number}. Alasan: " . \Illuminate\Support\Str::limit($this->requestAccessReason, 60),
            'url' => route('library.index', ['tab' => 'pending_approvals', 'highlight' => "req-{$request->id}"]),
            'type' => 'private_access_requested',
            'loan_code' => $doc->document_number,
        ]);
        $this->dispatch('loan-status-changed');

        $this->dispatch('notify', [
            'message' => "Permohonan akses berkas {$doc->document_number} berhasil diajukan ke Tapersip.",
            'type' => 'success'
        ]);

        $this->reset(['requestAccessDocId', 'requestAccessDocTitle', 'requestAccessDocNumber', 'requestAccessReason']);
    }

    public function openApproveAccessModal(int $requestId): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menyetujui akses privat.', 'type' => 'error']);
            return;
        }

        $req = DigitalDocumentAccessRequest::with(['document', 'user'])->findOrFail($requestId);
        $this->approvalRequestId = $req->id;
        $this->approvalDocTitle = $req->document->title ?? '-';
        $this->approvalDocNumber = $req->document->document_number ?? '-';
        $this->approvalUserName = $req->user->name ?? '-';
        $this->approvalUserReason = $req->reason;
        $this->approvalDurationHours = 24;

        $this->dispatch('open-modal', 'modal-approve-private-access');
    }

    public function executeApproveAccess(): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        if (!$this->approvalRequestId) return;

        $this->validate([
            'approvalDurationHours' => 'required|integer|in:2,6,24,72,168',
        ]);

        $req = DigitalDocumentAccessRequest::with('document')->findOrFail($this->approvalRequestId);
        $hours = (int) $this->approvalDurationHours;
        $expiresAt = now()->addHours($hours);

        $req->update([
            'status' => 'approved',
            'duration_hours' => $hours,
            'expires_at' => $expiresAt,
            'approved_by' => Auth::id(),
        ]);

        AuditLog::record(
            action: "Persetujuan Izin Akses Berkas Privat: {$req->document->document_number} selama {$hours} jam",
            documentId: null,
            metadata: [
                'request_id' => $req->id,
                'applicant_id' => $req->user_id,
                'duration_hours' => $hours,
                'expires_at' => $expiresAt->toDateTimeString(),
                'approved_by' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-approve-private-access');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => "Izin akses berkas {$req->document->document_number} berhasil diberikan ({$hours} Jam).",
            'type' => 'success'
        ]);

        $this->reset(['approvalRequestId', 'approvalDocTitle', 'approvalDocNumber', 'approvalUserName', 'approvalUserReason']);
    }

    public function openRejectAccessModal(int $requestId): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menolak akses privat.', 'type' => 'error']);
            return;
        }

        $req = DigitalDocumentAccessRequest::with(['document', 'user'])->findOrFail($requestId);
        $this->rejectionRequestId = $req->id;
        $this->approvalDocTitle = $req->document->title ?? '-';
        $this->approvalDocNumber = $req->document->document_number ?? '-';
        $this->approvalUserName = $req->user->name ?? '-';
        $this->rejectionReasonNotes = '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-reject-private-access');
    }

    public function executeRejectAccess(): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        if (!$this->rejectionRequestId) return;

        $this->validate([
            'rejectionReasonNotes' => 'required|string|min:5|max:500',
        ], [
            'rejectionReasonNotes.required' => 'Mohon cantumkan alasan penolakan izin akses.',
        ]);

        $req = DigitalDocumentAccessRequest::with('document')->findOrFail($this->rejectionRequestId);

        $req->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReasonNotes,
            'approved_by' => Auth::id(),
        ]);

        AuditLog::record(
            action: "Penolakan Izin Akses Berkas Privat: {$req->document->document_number}",
            documentId: null,
            metadata: [
                'request_id' => $req->id,
                'applicant_id' => $req->user_id,
                'reason' => $this->rejectionReasonNotes,
                'rejected_by' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-reject-private-access');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => "Permohonan akses berkas {$req->document->document_number} telah ditolak.",
            'type' => 'info'
        ]);

        $this->reset(['rejectionRequestId', 'rejectionReasonNotes']);
    }

    public function confirmDelete(int $docId): void
    {
        $doc = DigitalDocument::findOrFail($docId);
        $this->deleteDocId = $doc->id;
        $this->deleteDocTitle = "{$doc->document_number} - {$doc->title}";

        $this->dispatch('open-modal', 'modal-delete-digital-doc');
    }

    public function executeDelete(): void
    {
        if (!$this->deleteDocId) return;

        $doc = DigitalDocument::findOrFail($this->deleteDocId);
        $title = "{$doc->document_number} - {$doc->title}";
        $doc->delete();

        AuditLog::record(
            action: "Hapus Berkas Pustaka Digital: {$title}",
            documentId: null,
            metadata: ['document_id' => $this->deleteDocId, 'deleted_by' => Auth::id()]
        );

        $this->dispatch('close-modal', 'modal-delete-digital-doc');
        $this->dispatch('notify', [
            'message' => "Berkas {$title} berhasil dihapus.",
            'type' => 'warning'
        ]);

        $this->reset(['deleteDocId', 'deleteDocTitle']);
        if ($this->previewDocId === $doc->id) {
            $this->previewDoc = null;
            $this->previewDocId = null;
        }
    }

    public function getCanManageFlexibilityProperty(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        return $user->isSuperAdmin() || $user->hasModuleAccess('library_flexibility');
    }

    public function getCanUploadAndCreateFolderProperty(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        return $user->isSuperAdmin() || $user->hasModuleAccess('upload_digital_library');
    }

    public function openMoveDocModal(int $docId): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses fleksibilitas pemindahan berkas.', 'type' => 'error']);
            return;
        }

        $doc = DigitalDocument::findOrFail($docId);
        $this->moveDocId = $doc->id;
        $this->moveDocTitle = $doc->title;
        $this->moveDocNumber = $doc->document_number;
        $this->moveTargetFolderId = $doc->folder_id;

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-move-digital-doc');
    }

    public function executeMoveDoc(): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        if (!$this->moveDocId) return;

        $this->validate([
            'moveTargetFolderId' => 'nullable|exists:digital_folders,id',
        ]);

        $doc = DigitalDocument::findOrFail($this->moveDocId);
        $doc->update(['folder_id' => $this->moveTargetFolderId]);

        $targetName = $this->moveTargetFolderId ? DigitalFolder::find($this->moveTargetFolderId)?->name : 'Root (Tanpa Folder)';

        AuditLog::record(
            action: "Pemindahan Berkas Pustaka Digital: {$doc->document_number} ke folder '{$targetName}'",
            documentId: null,
            metadata: ['document_id' => $doc->id, 'target_folder_id' => $this->moveTargetFolderId, 'user_id' => Auth::id()]
        );

        $this->dispatch('close-modal', 'modal-move-digital-doc');
        $this->dispatch('notify', [
            'message' => "Berkas {$doc->document_number} berhasil dipindahkan ke '{$targetName}'.",
            'type' => 'success'
        ]);

        $this->reset(['moveDocId', 'moveDocTitle', 'moveDocNumber', 'moveTargetFolderId']);
    }

    public function openMoveFolderModal(int $folderId): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses fleksibilitas pemindahan folder.', 'type' => 'error']);
            return;
        }

        $folder = DigitalFolder::findOrFail($folderId);

        if ($folder->children()->exists()) {
            $this->dispatch('notify', [
                'message' => "Folder '{$folder->name}' memiliki subfolder sehingga tidak dapat dipindahkan.",
                'type' => 'error'
            ]);
            return;
        }

        $this->moveFolderId = $folder->id;
        $this->moveFolderName = $folder->name;
        $this->moveTargetParentId = $folder->parent_id;

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'modal-move-digital-folder');
    }

    public function executeMoveFolder(): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        if (!$this->moveFolderId) return;

        $folder = DigitalFolder::findOrFail($this->moveFolderId);

        if ($folder->children()->exists()) {
            $this->addError('moveTargetParentId', "Folder '{$folder->name}' memiliki subfolder sehingga tidak dapat dipindahkan.");
            return;
        }

        $this->validate([
            'moveTargetParentId' => 'nullable|exists:digital_folders,id',
        ]);

        if ($this->moveTargetParentId && $this->moveTargetParentId == $this->moveFolderId) {
            $this->addError('moveTargetParentId', 'Folder tidak dapat dipindahkan ke dalam dirinya sendiri.');
            return;
        }

        if ($this->moveTargetParentId && $this->isDescendantFolder($this->moveFolderId, (int) $this->moveTargetParentId)) {
            $this->addError('moveTargetParentId', 'Folder tujuan merupakan subfolder turunan dari folder ini.');
            return;
        }

        $folder->update(['parent_id' => $this->moveTargetParentId]);

        $targetName = $this->moveTargetParentId ? DigitalFolder::find($this->moveTargetParentId)?->name : 'Root (Folder Utama)';

        AuditLog::record(
            action: "Pemindahan Hierarki Folder Pustaka: '{$folder->name}' ke '{$targetName}'",
            documentId: null,
            metadata: ['folder_id' => $folder->id, 'new_parent_id' => $this->moveTargetParentId, 'user_id' => Auth::id()]
        );

        $this->dispatch('close-modal', 'modal-move-digital-folder');
        $this->dispatch('notify', [
            'message' => "Folder '{$folder->name}' berhasil dipindahkan ke '{$targetName}'.",
            'type' => 'success'
        ]);

        $this->reset(['moveFolderId', 'moveFolderName', 'moveTargetParentId']);
    }

    protected function isDescendantFolder(int $folderId, int $targetParentId): bool
    {
        $current = DigitalFolder::find($targetParentId);
        while ($current) {
            if ($current->id === $folderId) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }

    public function confirmDeleteFolder(int $folderId): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk menghapus folder.', 'type' => 'error']);
            return;
        }

        $folder = DigitalFolder::findOrFail($folderId);

        if ($folder->children()->exists()) {
            $this->dispatch('notify', [
                'message' => "Folder '{$folder->name}' memiliki subfolder sehingga tidak dapat dihapus. Hapus atau pindahkan subfolder terlebih dahulu.",
                'type' => 'error'
            ]);
            return;
        }

        $this->deleteFolderId = $folder->id;
        $this->deleteFolderName = $folder->name;
        $this->deleteFolderDocsCount = $folder->documents()->count();
        $this->deleteFolderSubCount = 0;

        $this->dispatch('open-modal', 'modal-delete-digital-folder');
    }

    public function executeDeleteFolder(): void
    {
        if (!$this->canManageFlexibility) {
            $this->dispatch('notify', ['message' => 'Akses ditolak.', 'type' => 'error']);
            return;
        }

        if (!$this->deleteFolderId) return;

        $folder = DigitalFolder::findOrFail($this->deleteFolderId);

        if ($folder->children()->exists()) {
            $this->dispatch('notify', [
                'message' => "Folder '{$folder->name}' memiliki subfolder sehingga tidak dapat dihapus.",
                'type' => 'error'
            ]);
            return;
        }

        $folderName = $folder->name;
        $fallbackParentId = $folder->parent_id;

        // Zero Data Loss: Re-assign all documents in this folder to the parent folder (or root)
        DigitalDocument::where('folder_id', $folder->id)->update(['folder_id' => $fallbackParentId]);

        $folder->delete();

        if ($this->activeFolderId === $this->deleteFolderId) {
            $this->activeFolderId = $fallbackParentId;
        }

        AuditLog::record(
            action: "Penghapusan Folder Pustaka: '{$folderName}' (seluruh berkas dialihkan dengan aman)",
            documentId: null,
            metadata: ['folder_name' => $folderName, 'user_id' => Auth::id()]
        );

        $this->dispatch('close-modal', 'modal-delete-digital-folder');
        $this->dispatch('notify', [
            'message' => "Folder '{$folderName}' berhasil dihapus. Seluruh berkas di dalamnya dialihkan dengan aman.",
            'type' => 'warning'
        ]);

        $this->reset(['deleteFolderId', 'deleteFolderName', 'deleteFolderDocsCount', 'deleteFolderSubCount']);
    }

    public function render()
    {
        $query = DigitalDocument::with(['unit', 'folder', 'creator', 'accessRequests']);

        // Folder Filtering
        if ($this->activeFolderId) {
            $query->where('folder_id', $this->activeFolderId);
        }

        // Category Filter
        if ($this->filterCategory && $this->filterCategory !== 'all') {
            $query->where('category', $this->filterCategory);
        }

        // Year Filter
        if ($this->filterYear) {
            $query->where('year', (int) $this->filterYear);
        }

        // Unit Filter
        if ($this->filterUnit) {
            $query->where('unit_id', $this->filterUnit);
        }

        // Tag Filter
        if ($this->selectedTag) {
            $tag = $this->selectedTag;
            $query->where(function ($q) use ($tag) {
                $q->where('tags', 'like', "%#{$tag}%")
                  ->orWhere('tags', 'like', "%{$tag}%");
            });
        }

        // Global Search (title, doc_number, tags, file_name, description)
        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('document_number', 'like', "%{$term}%")
                  ->orWhere('tags', 'like', "%{$term}%")
                  ->orWhere('file_name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $documents = $query->orderBy('id', 'desc')->paginate(12, ['*'], 'documentsPage');

        $myRequests = null;
        $pendingApprovals = null;

        if ($this->activeTab === 'my_requests') {
            $myRequests = DigitalDocumentAccessRequest::with(['document.unit', 'approver'])
                ->where('user_id', Auth::id())
                ->latest('id')
                ->paginate(10, ['*'], 'requestsPage');
        } elseif ($this->activeTab === 'pending_approvals') {
            $pendingApprovals = DigitalDocumentAccessRequest::with(['document.unit', 'user.unit'])
                ->where('status', 'pending')
                ->latest('id')
                ->paginate(10, ['*'], 'approvalsPage');
        }

        $pendingApprovalsCount = DigitalDocumentAccessRequest::where('status', 'pending')->count();
        $myRequestsCount = DigitalDocumentAccessRequest::where('user_id', Auth::id())->count();

        // Sidebar folders (root and children with deep eager loading)
        $folders = DigitalFolder::with(['children.children.children.children.children', 'children.documents', 'documents'])->whereNull('parent_id')->orderBy('name')->get();
        $allFolders = DigitalFolder::getTree();
        $activeFolder = $this->activeFolderId ? DigitalFolder::find($this->activeFolderId) : null;

        $expandedFolderIds = [];
        if ($this->activeFolderId) {
            $actFolder = DigitalFolder::find($this->activeFolderId);
            if ($actFolder) {
                $expandedFolderIds = array_merge([$actFolder->id], $actFolder->getAncestorIds());
            }
        }

        // Statistics for header badges
        $totalDocsCount = DigitalDocument::count();
        $availableUnits = Unit::orderBy('name')->get();
        $availableYears = DigitalDocument::select('year')->distinct()->orderByDesc('year')->pluck('year')->toArray();

        return view('livewire.library.digital-library-index', [
            'documents' => $documents,
            'myRequests' => $myRequests,
            'pendingApprovals' => $pendingApprovals,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'myRequestsCount' => $myRequestsCount,
            'folders' => $folders,
            'allFolders' => $allFolders,
            'activeFolder' => $activeFolder,
            'expandedFolderIds' => $expandedFolderIds,
            'totalDocsCount' => $totalDocsCount,
            'availableUnits' => $availableUnits,
            'availableYears' => $availableYears,
            'categories' => DigitalDocument::CATEGORIES,
            'canManageFlexibility' => $this->canManageFlexibility,
            'canUploadAndCreateFolder' => $this->canUploadAndCreateFolder,
        ])->layout('components.layouts.app', ['title' => 'Pustaka Digital & Dokumen Proyek']);
    }
}
