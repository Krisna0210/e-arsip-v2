<?php

namespace App\Livewire\Registry\Internal;

use App\Models\Document;
use App\Models\Folder;
use App\Models\DocumentVersion;
use App\Models\Classification;
use App\Models\Unit;
use App\Models\AuditLog;
use App\Services\NasHealthService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination, WithFileUploads;

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

    // State Koreksi Metadata Arsip Permanen
    public $editDocId = null;
    public $editDocNumber = '';
    public $editSubject = '';
    public $editDocumentDate = '';
    public $editRecipientId = null;
    public $editSifat = 'biasa';
    public $editClassificationId = null;
    public $originalClassificationId = null;
    public $originalDocumentDate = '';
    public $yearChangeWarning = '';
    public $syncDocNumberWithClassification = false;
    public $newDocNumberPreview = '';
    public $editReason = '';

    // State Unggah Berkas Revisi PDF (v2.0, v3.0, dst.)
    public $revisionDocId = null;
    public $revisionDocNumber = '';
    public $revisionDocSubject = '';
    public $revisionCurrentVersion = '1.0';
    public $revisionNextVersion = '2.0';
    public $revisionFile = null;
    public $revisionReason = '';

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

        if ($doc->archive_status === 'disposed') {
            $this->dispatch('notify', ['message' => 'Dokumen ini telah berstatus Dimusnahkan dan tidak dapat dipindahkan loker berkasnya.', 'type' => 'error']);
            return;
        }

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

    public function openEditMetadataModal($docId)
    {
        $doc = Document::with(['folder', 'classification', 'recipients'])->findOrFail($docId);

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && !Auth::user()->hasDocumentPermission('archive', $doc->sifat ?? 'biasa')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki wewenang untuk mengoreksi arsip ini.', 'type' => 'error']);
            return;
        }

        $this->editDocId = $doc->id;
        $this->editDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->editSubject = $doc->subject;
        $this->editDocumentDate = $doc->document_date ? $doc->document_date->format('Y-m-d') : date('Y-m-d');
        $this->originalDocumentDate = $this->editDocumentDate;
        $this->editRecipientId = $doc->recipients()->first()?->recipient_id;
        $this->editSifat = $doc->sifat ?? 'biasa';
        $this->editClassificationId = $doc->classification_id;
        $this->originalClassificationId = $doc->classification_id;
        $this->syncDocNumberWithClassification = false;
        $this->newDocNumberPreview = '';
        $this->yearChangeWarning = '';
        $this->editReason = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'modal-edit-archived-metadata');
    }

    public function updatedEditClassificationId()
    {
        $this->calculateNewNumberPreview();
    }

    public function updatedEditDocumentDate()
    {
        $this->calculateNewNumberPreview();
    }

    protected function calculateNewNumberPreview()
    {
        $this->newDocNumberPreview = '';
        $this->syncDocNumberWithClassification = false;
        $this->yearChangeWarning = '';

        if (!$this->editDocId || !$this->editDocNumber || $this->editDocNumber === 'Tanpa Nomor') {
            return;
        }

        $candidateNumber = $this->editDocNumber;
        $hasChanges = false;

        // 1. Cek perubahan klasifikasi jika ada
        if ($this->editClassificationId && $this->originalClassificationId && $this->editClassificationId != $this->originalClassificationId) {
            $origClass = Classification::find($this->originalClassificationId);
            $newClass = Classification::find($this->editClassificationId);

            if ($origClass?->code && $newClass?->code && str_contains($candidateNumber, $origClass->code)) {
                $candidateNumber = str_replace($origClass->code, $newClass->code, $candidateNumber);
                $hasChanges = true;
            }
        }

        // 2. Cek perubahan tanggal dokumen (Bulan & Tahun)
        if ($this->originalDocumentDate && $this->editDocumentDate) {
            $origYear = date('Y', strtotime($this->originalDocumentDate));
            $newYear = date('Y', strtotime($this->editDocumentDate));
            $origMonth = date('m', strtotime($this->originalDocumentDate));
            $newMonth = date('m', strtotime($this->editDocumentDate));

            if ($origYear !== $newYear) {
                $this->yearChangeWarning = "Perubahan tahun ({$origYear} ke {$newYear}) tidak mengubah segmen tahun pada nomor surat demi menjaga integritas buku agenda tahunan. Jika salah tahun penerbitan, gunakan fitur Pembatalan Nomor (Void).";
            }

            if ($origMonth !== $newMonth) {
                if (str_contains($candidateNumber, '/' . $origMonth . '/')) {
                    $candidateNumber = str_replace('/' . $origMonth . '/', '/' . $newMonth . '/', $candidateNumber);
                    $hasChanges = true;
                } elseif (str_contains($candidateNumber, '/' . $origMonth . '-')) {
                    $candidateNumber = str_replace('/' . $origMonth . '-', '/' . $newMonth . '-', $candidateNumber);
                    $hasChanges = true;
                } elseif (str_contains($candidateNumber, '-' . $origMonth . '/')) {
                    $candidateNumber = str_replace('-' . $origMonth . '/', '-' . $newMonth . '/', $candidateNumber);
                    $hasChanges = true;
                }
            }
        }

        if ($hasChanges && $candidateNumber !== $this->editDocNumber) {
            $this->newDocNumberPreview = $candidateNumber;
            $this->syncDocNumberWithClassification = true;
        }
    }

    public function saveEditedMetadata()
    {
        if (!$this->editDocId) return;

        $document = Document::with('recipients')->findOrFail($this->editDocId);

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && !Auth::user()->hasDocumentPermission('archive', $document->sifat ?? 'biasa')) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'editSubject' => 'required|string|max:255',
            'editDocumentDate' => 'required|date|before_or_equal:today',
            'editRecipientId' => 'required|exists:units,id',
            'editSifat' => 'required|in:biasa,penting,rahasia',
            'editClassificationId' => 'required|exists:classifications,id',
            'editReason' => 'required|string|min:3|max:500',
        ], [
            'editSubject.required' => 'Perihal dokumen wajib diisi.',
            'editDocumentDate.required' => 'Tanggal dokumen wajib diisi.',
            'editRecipientId.required' => 'Unit/Departemen penerima surat khusus wajib dipilih.',
            'editReason.required' => 'Mohon cantumkan alasan koreksi dokumen arsip.',
            'editReason.min' => 'Alasan koreksi minimal 3 karakter.',
        ]);

        $oldNumber = $document->number;
        $newNumber = $oldNumber;

        if ($this->syncDocNumberWithClassification && $this->newDocNumberPreview && $this->newDocNumberPreview !== $oldNumber) {
            $newNumber = $this->newDocNumberPreview;
        }

        $before = [
            'number' => $oldNumber,
            'subject' => $document->subject,
            'document_date' => $document->document_date?->format('Y-m-d'),
            'recipient_id' => $document->recipients()->first()?->recipient_id,
            'sifat' => $document->sifat,
            'classification_id' => $document->classification_id,
        ];

        $document->update([
            'number' => $newNumber,
            'subject' => $this->editSubject,
            'document_date' => $this->editDocumentDate,
            'sifat' => $this->editSifat,
            'classification_id' => $this->editClassificationId,
        ]);

        $document->recipients()->delete();
        $document->recipients()->create([
            'recipient_id' => $this->editRecipientId,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);

        $after = [
            'number' => $newNumber,
            'subject' => $document->subject,
            'document_date' => $this->editDocumentDate,
            'recipient_id' => $this->editRecipientId,
            'sifat' => $this->editSifat,
            'classification_id' => $this->editClassificationId,
        ];

        AuditLog::record(
            action: 'Koreksi Metadata Dokumen Arsip: ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'reason' => $this->editReason,
                'before' => $before,
                'after' => $after,
                'editor_id' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-edit-archived-metadata');
        $this->dispatch('notify', [
            'message' => "Metadata dokumen arsip {$document->number} berhasil dikoreksi.",
            'type' => 'success'
        ]);
        $this->reset(['editDocId', 'editDocNumber', 'editSubject', 'editDocumentDate', 'editRecipientId', 'editReason', 'originalClassificationId', 'originalDocumentDate', 'yearChangeWarning', 'syncDocNumberWithClassification', 'newDocNumberPreview']);
    }

    public function openRevisionModal($docId)
    {
        $doc = Document::with(['currentVersion'])->findOrFail($docId);

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin()) {
            $this->dispatch('notify', ['message' => 'Hanya petugas Tapersip yang berwenang mengunggah berkas revisi arsip.', 'type' => 'error']);
            return;
        }

        if ($doc->status !== 'archived') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang berstatus Arsip Permanen yang dapat diunggah berkas revisinya.', 'type' => 'error']);
            return;
        }

        if ($doc->archive_status === 'disposed') {
            $this->dispatch('notify', ['message' => 'Dokumen ini telah resmi Dimusnahkan dan tidak dapat diunggah revisinya.', 'type' => 'error']);
            return;
        }

        $nasStatus = NasHealthService::check();
        if (!$nasStatus['online']) {
            $this->dispatch('notify', ['message' => 'Penyimpanan server NAS sedang terputus. Tidak dapat mengunggah revisi berkas saat ini.', 'type' => 'error']);
            return;
        }

        $currentVer = $doc->currentVersion?->version_number ?? '1.0';
        $currentFloat = (float) $currentVer;
        $nextVer = number_format(floor($currentFloat) + 1.0, 1, '.', '');

        $this->revisionDocId = $doc->id;
        $this->revisionDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->revisionDocSubject = $doc->subject;
        $this->revisionCurrentVersion = $currentVer;
        $this->revisionNextVersion = $nextVer;
        $this->revisionFile = null;
        $this->revisionReason = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'modal-revision-upload');
    }

    public function saveRevisionFile()
    {
        if (!$this->revisionDocId) return;

        $document = Document::with(['folder.parent', 'documentType', 'currentVersion'])->findOrFail($this->revisionDocId);

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'revisionFile' => 'required|file|mimes:pdf|max:10240',
            'revisionReason' => 'required|string|min:3|max:500',
        ], [
            'revisionFile.required' => 'Berkas PDF hasil scan revisi wajib dipilih.',
            'revisionFile.mimes' => 'Berkas harus dalam format PDF.',
            'revisionReason.required' => 'Mohon cantumkan alasan revisi berkas scan.',
            'revisionReason.min' => 'Alasan revisi minimal 3 karakter.',
        ]);

        $nasStatus = NasHealthService::check(true);
        if (!$nasStatus['online']) {
            $this->addError('revisionFile', 'Penyimpanan NAS sedang tidak terhubung. Mohon periksa koneksi server.');
            return;
        }

        $directory = $document->getStorageDirectoryPath();
        $fileName = $document->generateArchiveFileName('pdf', $this->revisionNextVersion);
        $path = $this->revisionFile->storeAs($directory, $fileName, 'nas');

        // Nonaktifkan versi aktif sebelumnya menjadi obsolete
        DocumentVersion::where('document_id', $document->id)
            ->where('status', 'active')
            ->update(['status' => 'obsolete']);

        // Buat versi baru aktif
        $newVersion = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $this->revisionNextVersion,
            'file_path' => $path,
            'created_by' => Auth::id(),
            'status' => 'active',
        ]);

        $document->update([
            'current_version_id' => $newVersion->id,
        ]);

        AuditLog::record(
            action: "Unggah Berkas Revisi Arsip (v{$this->revisionNextVersion}): " . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'previous_version' => $this->revisionCurrentVersion,
                'new_version' => $this->revisionNextVersion,
                'reason' => $this->revisionReason,
                'file_path' => $path,
                'uploaded_by' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-revision-upload');
        $this->dispatch('notify', [
            'message' => "Berkas revisi versi {$this->revisionNextVersion} untuk naskah {$document->number} berhasil diarsipkan.",
            'type' => 'success'
        ]);
        $this->reset(['revisionDocId', 'revisionDocNumber', 'revisionDocSubject', 'revisionFile', 'revisionReason']);
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
        $classifications = Classification::whereNotNull('parent_id')->orderBy('code')->get();
        $availableUnits = Unit::where('status', 'active')->orderBy('name')->get();

        $query = Document::with(['folder', 'documentType', 'classification', 'unit', 'recipients.recipient', 'currentVersion'])
            ->where('type', 'internal');

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isTapersip()) {
            $canViewRahasia = $user->hasDocumentPermission('view', 'rahasia');
            $canViewPenting = $user->hasDocumentPermission('view', 'penting');
            $userId = $user->id;
            $userUnitId = $user->unit_id;

            $query->where(function ($q) use ($canViewRahasia, $canViewPenting, $userId, $userUnitId) {
                $q->where('sifat', 'biasa')
                  ->orWhere('created_by', $userId);

                if ($canViewRahasia) {
                    $q->orWhere('sifat', 'rahasia');
                }

                if ($canViewPenting) {
                    $q->orWhere('sifat', 'penting');
                } elseif ($userUnitId) {
                    $q->orWhere(function ($qp) use ($userUnitId) {
                        $qp->where('sifat', 'penting')
                           ->where(function ($qu) use ($userUnitId) {
                               $qu->where('unit_id', $userUnitId)
                                  ->orWhereHas('recipients', function ($qr) use ($userUnitId) {
                                      $qr->where('recipient_id', $userUnitId);
                                  });
                           });
                    });
                }
            });
        }

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
            'classifications' => $classifications,
            'availableUnits' => $availableUnits,
            'documents' => $documents,
        ]);
    }
}




