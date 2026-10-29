<?php

namespace App\Livewire\Registry;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Classification;
use App\Models\Unit;
use App\Models\AuditLog;
use App\Notifications\DocumentArchivedNotification;
use App\Services\NasHealthService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

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

    // State Koreksi Metadata Draft (waiting_for_file)
    public $editDocId = null;
    public $editDocNumber = '';
    public $editDocType = '';
    public $editDocTypeRaw = '';
    public $editSubject = '';
    public $editDocumentDate = '';
    public $editRecipientName = '';
    public $editRecipientId = null;
    public $editSifat = 'biasa';
    public $editClassificationId = null;
    public $originalClassificationId = null;
    public $originalDocumentDate = '';
    public $yearChangeWarning = '';
    public $syncDocNumberWithClassification = false;
    public $newDocNumberPreview = '';
    public $editReason = '';

    // State Pembatalan Nomor Surat Resmi (Void)
    public $voidDocId = null;
    public $voidDocNumber = '';
    public $voidReason = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
    ];

    #[On('loan-status-changed')]
    public function handleStatusChanged(): void
    {
        // Re-render antrean scan saat ada pembaruan dokumen
    }

    public function mount()
    {
        $highlight = request()->query('highlight');
        if ($highlight && preg_match('/^doc-(\d+)$/', $highlight, $matches)) {
            $targetDocId = (int) $matches[1];
            $targetDoc = Document::where('status', 'waiting_for_file')
                ->whereIn('type', ['outgoing', 'internal'])
                ->find($targetDocId);

            if ($targetDoc) {
                // Jika tipe dokumen spesifik tapi filterType tidak cocok, sesuaikan ke 'all'
                if ($this->filterType !== 'all' && $this->filterType !== $targetDoc->type) {
                    $this->filterType = 'all';
                }

                // Hitung posisi antrean dokumen target berdasarkan urutan render (created_at asc, id asc)
                $rankQuery = Document::where('status', 'waiting_for_file')
                    ->whereIn('type', ['outgoing', 'internal']);

                if ($this->filterType !== 'all') {
                    $rankQuery->where('type', $this->filterType);
                }

                $position = $rankQuery->where(function ($q) use ($targetDoc) {
                    $q->where('created_at', '<', $targetDoc->created_at)
                      ->orWhere(function ($q2) use ($targetDoc) {
                          $q2->where('created_at', '=', $targetDoc->created_at)
                             ->where('id', '<', $targetDoc->id);
                      });
                })->count() + 1;

                $targetPage = (int) ceil($position / 10);
                if ($targetPage > 1) {
                    $this->setPage($targetPage);
                }
            }
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function setFilterType($type)
    {
        $this->filterType = $type;
        $this->resetPage();
    }

    public function getNasStatusProperty()
    {
        return NasHealthService::check();
    }

    public function refreshNasConnection()
    {
        NasHealthService::check(true);
        $this->dispatch('notify', [
            'message' => 'Status koneksi NAS telah diperbarui.',
            'type' => 'info',
        ]);
    }

    public function openUploadModal($id)
    {
        $doc = Document::with(['createdBy', 'documentType'])->findOrFail($id);
        
        // Cek permission archive (termasuk superadmin check)
        if (!Auth::user()->hasDocumentPermission('archive', $doc->sifat ?? 'biasa')) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk mengarsipkan dokumen ini.', 'type' => 'error']);
            return;
        }

        $this->uploadDocId = $doc->id;
        $this->uploadDocNumber = $doc->number;
        $this->uploadDocSubject = $doc->subject;
        $this->uploadDocDate = $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->format('d M Y') : '-';
        $this->uploadDocType = $doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus');
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

        // Cek konektivitas NAS sebelum menyimpan file fisik
        $nasStatus = NasHealthService::check(true);
        if (!$nasStatus['online']) {
            $this->addError('finalFile', 'Penyimpanan NAS sedang tidak terhubung atau tidak dapat diakses. Mohon periksa koneksi server NAS sebelum mengarsipkan.');
            return;
        }
        
        // Tetapkan loker terlebih dahulu agar struktur direktori di NAS sesuai
        $document->folder_id = $this->uploadFolderId;
        $document->load(['folder.parent', 'documentType']);

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

        $classification = $document->classification;
        $retentionStartDate = null;
        $retentionEndDate = null;
        if ($classification && $classification->active_years > 0) {
            $retentionStartDate = $document->document_date ?: date('Y-m-d');
            $retentionEndDate = date('Y-m-d', strtotime("+{$classification->active_years} years", strtotime($retentionStartDate)));
        }

        $document->update([
            'current_version_id' => $version->id,
            'status' => 'archived',
            'folder_id' => $this->uploadFolderId,
            'retention_start_date' => $retentionStartDate,
            'retention_end_date' => $retentionEndDate,
        ]);

        $typeLabel = $document->type === 'internal'
            ? ($document->documentType?->name ?? 'Surat Khusus')
            : ($document->documentType?->name ?? 'Surat Keluar');

        \App\Models\AuditLog::record(
            action: 'Unggah Scan Fisik Arsip: ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'subject' => $document->subject,
                'type' => $typeLabel,
                'folder_id' => $this->uploadFolderId,
            ]
        );

        // Kirim notifikasi email eksklusif kepada staf pembuat naskah (asinkron via defer, non-blocking & fail-safe)
        $document->loadMissing(['createdBy', 'documentType', 'folder.parent']);
        $creator = $document->createdBy;
        $officer = Auth::user();
        $emailSentTo = null;

        if ($creator && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
            $emailSentTo = $creator->name;
            $sendNotification = function () use ($creator, $document, $officer, $version) {
                try {
                    $creator->notify(new DocumentArchivedNotification($document, $officer, $version));
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim email notifikasi arsip: ' . $e->getMessage());
                }
            };

            if (app()->runningUnitTests()) {
                $sendNotification();
            } else {
                defer($sendNotification);
            }
        }

        $this->reset(['uploadDocId', 'finalFile', 'uploadDocNumber', 'uploadDocSubject', 'uploadDocDate', 'uploadDocType', 'uploadDocTypeRaw', 'uploadFolderId', 'uploadDocCreator']);
        $this->dispatch('close-modal', 'modal-upload-queue');

        $targetUrl = $document->type === 'internal'
            ? route('registry.special.index', ['highlight' => "doc-{$document->id}"])
            : route('registry.outgoing.index', ['highlight' => "doc-{$document->id}"]);

        if ($emailSentTo) {
            $this->dispatch('desktop-notify', [
                'title' => 'Dokumen Berhasil Diarsipkan',
                'body' => "Naskah {$document->number} resmi diarsipkan. Email notifikasi telah dikirim ke {$creator->name} ({$creator->email}).",
                'url' => $targetUrl,
                'type' => 'scan_archived_success',
                'loan_code' => $document->number,
            ]);
        } else {
            $this->dispatch('desktop-notify', [
                'title' => 'Dokumen Berhasil Diarsipkan',
                'body' => "Naskah {$document->number} resmi diarsipkan ke direktori penyimpanan NAS.",
                'url' => $targetUrl,
                'type' => 'scan_archived_success',
                'loan_code' => $document->number,
            ]);
        }

        $this->dispatch('loan-status-changed');

        $successMsg = $emailSentTo
            ? "File PDF Final berhasil diunggah dan diarsipkan. Notifikasi email telah dikirim ke {$emailSentTo}."
            : "File PDF Final berhasil diunggah dan diarsipkan.";
        $this->dispatch('notify', ['message' => $successMsg, 'type' => 'success']);
    }

    public function openEditMetadataModal($id)
    {
        $doc = Document::with(['createdBy', 'documentType', 'recipients'])->findOrFail($id);

        if ($doc->status !== 'waiting_for_file') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang berstatus Menunggu Upload yang dapat dikoreksi di antrean scan.', 'type' => 'error']);
            return;
        }

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && $doc->created_by !== Auth::id()) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk mengoreksi dokumen ini.', 'type' => 'error']);
            return;
        }

        $this->editDocId = $doc->id;
        $this->editDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->editDocType = $doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus');
        $this->editDocTypeRaw = $doc->type;
        $this->editSubject = $doc->subject;
        $this->editDocumentDate = $doc->document_date ? $doc->document_date->format('Y-m-d') : date('Y-m-d');
        $this->originalDocumentDate = $this->editDocumentDate;
        $this->editRecipientName = $doc->recipient_name ?? '';
        $this->editRecipientId = $doc->recipients()->first()?->recipient_id;
        $this->editSifat = $doc->sifat ?? 'biasa';
        $this->editClassificationId = $doc->classification_id;
        $this->originalClassificationId = $doc->classification_id;
        $this->syncDocNumberWithClassification = false;
        $this->newDocNumberPreview = '';
        $this->yearChangeWarning = '';
        $this->editReason = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'modal-edit-queue-metadata');
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

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && $document->created_by !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'editSubject' => 'required|string|max:255',
            'editDocumentDate' => 'required|date|before_or_equal:today',
            'editSifat' => 'required|in:biasa,penting,rahasia',
            'editClassificationId' => 'required|exists:classifications,id',
            'editReason' => 'required|string|min:3|max:500',
        ];

        if ($this->editDocTypeRaw === 'outgoing') {
            $rules['editRecipientName'] = 'required|string|max:255';
        } elseif ($this->editDocTypeRaw === 'internal') {
            $rules['editRecipientId'] = 'required|exists:units,id';
        }

        $this->validate($rules, [
            'editSubject.required' => 'Perihal dokumen wajib diisi.',
            'editDocumentDate.required' => 'Tanggal dokumen wajib diisi.',
            'editReason.required' => 'Mohon cantumkan alasan koreksi dokumen.',
            'editReason.min' => 'Alasan koreksi minimal 3 karakter.',
            'editRecipientName.required' => 'Tujuan penerima surat keluar wajib diisi.',
            'editRecipientId.required' => 'Departemen penerima surat khusus wajib dipilih.',
        ]);

        $oldNumber = $document->number;
        $newNumber = $oldNumber;

        if ($this->syncDocNumberWithClassification && $this->newDocNumberPreview && $this->newDocNumberPreview !== $oldNumber) {
            $newNumber = $this->newDocNumberPreview;
            $document->number = $newNumber;
        }

        $before = [
            'number' => $oldNumber,
            'subject' => $document->subject,
            'document_date' => $document->document_date?->format('Y-m-d'),
            'sifat' => $document->sifat,
            'classification_id' => $document->classification_id,
            'recipient' => $this->editDocTypeRaw === 'outgoing' ? $document->recipient_name : ($document->recipients()->first()?->recipient_id),
        ];

        $document->subject = $this->editSubject;
        $document->document_date = $this->editDocumentDate;
        $document->sifat = $this->editSifat;
        $document->classification_id = $this->editClassificationId;

        if ($this->editDocTypeRaw === 'outgoing') {
            $document->recipient_name = $this->editRecipientName;
        } elseif ($this->editDocTypeRaw === 'internal') {
            $document->recipients()->delete();
            $document->recipients()->create([
                'recipient_id' => $this->editRecipientId,
                'recipient_type' => Unit::class,
                'receipt_status' => 'unread',
            ]);
        }

        $document->save();

        $after = [
            'number' => $newNumber,
            'subject' => $document->subject,
            'document_date' => $this->editDocumentDate,
            'sifat' => $this->editSifat,
            'classification_id' => $this->editClassificationId,
            'recipient' => $this->editDocTypeRaw === 'outgoing' ? $this->editRecipientName : $this->editRecipientId,
        ];

        AuditLog::record(
            action: 'Koreksi Metadata Draft Naskah: ' . ($document->number ?: $document->subject),
            documentId: $document->id,
            metadata: [
                'reason' => $this->editReason,
                'before' => $before,
                'after' => $after,
                'editor_id' => Auth::id(),
            ]
        );

        $this->dispatch('close-modal', 'modal-edit-queue-metadata');
        $this->dispatch('notify', [
            'message' => "Metadata draft naskah {$document->number} berhasil dikoreksi.",
            'type' => 'success'
        ]);
        $this->reset(['editDocId', 'editDocNumber', 'editSubject', 'editDocumentDate', 'editRecipientName', 'editRecipientId', 'editReason', 'originalClassificationId', 'originalDocumentDate', 'yearChangeWarning', 'syncDocNumberWithClassification', 'newDocNumberPreview']);
    }

    public function openVoidModal($id)
    {
        $doc = Document::findOrFail($id);
        if ($doc->status !== 'waiting_for_file') {
            $this->dispatch('notify', ['message' => 'Hanya dokumen yang berstatus Menunggu Upload yang dapat dibatalkan nomornya.', 'type' => 'error']);
            return;
        }

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && $doc->created_by !== Auth::id()) {
            $this->dispatch('notify', ['message' => 'Anda tidak memiliki hak akses untuk membatalkan nomor dokumen ini.', 'type' => 'error']);
            return;
        }

        $this->voidDocId = $doc->id;
        $this->voidDocNumber = $doc->number ?: 'Tanpa Nomor';
        $this->voidReason = '';
        $this->resetErrorBag('voidReason');

        $this->dispatch('open-modal', 'modal-void-queue-document');
    }

    public function confirmVoidDocument()
    {
        if (!$this->voidDocId) return;

        $document = Document::findOrFail($this->voidDocId);

        if (!Auth::user()->isTapersip() && !Auth::user()->isSuperAdmin() && $document->created_by !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'voidReason' => 'required|string|min:3|max:500',
        ], [
            'voidReason.required' => 'Mohon cantumkan alasan pembatalan nomor surat.',
            'voidReason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        $isTapersipOrSuper = Auth::user()->isSuperAdmin() || Auth::user()->isTapersip();

        if ($isTapersipOrSuper) {
            $document->update([
                'status' => 'cancelled',
                'notes' => $this->voidReason,
                'cancelled_by' => Auth::id(),
            ]);

            AuditLog::record(
                action: 'Pembatalan Nomor Naskah Resmi (Void): ' . ($document->number ?: $document->subject),
                documentId: $document->id,
                metadata: [
                    'reason' => $this->voidReason,
                    'cancelled_by' => Auth::id(),
                ]
            );

            $msg = "Nomor naskah {$document->number} resmi dibatalkan (void). Urutan nomor tetap terjaga dalam audit trail.";
            $msgType = 'warning';
        } else {
            $document->update([
                'status' => 'cancellation_requested',
                'notes' => $this->voidReason,
                'cancellation_requested_at' => now(),
            ]);

            AuditLog::record(
                action: 'Permohonan Pembatalan Nomor Naskah: ' . ($document->number ?: $document->subject),
                documentId: $document->id,
                metadata: [
                    'reason' => $this->voidReason,
                    'requested_by' => Auth::id(),
                ]
            );

            $user = Auth::user();
            $unitName = $user?->unit?->name ? " ({$user->unit->name})" : '';
            $this->dispatch('desktop-notify', [
                'title' => 'Permohonan Batal Nomor Naskah',
                'body' => "{$user->name}{$unitName} mengajukan pembatalan naskah {$document->number}. Alasan: " . \Illuminate\Support\Str::limit($this->voidReason, 60),
                'url' => route('registry.cancelled.index', ['tab' => 'requests', 'highlight' => "doc-{$document->id}"]),
                'type' => 'cancellation_requested',
                'loan_code' => $document->number,
            ]);

            $msg = "Permohonan pembatalan naskah {$document->number} telah diajukan ke Tapersip untuk diverifikasi.";
            $msgType = 'info';
        }

        $this->dispatch('close-modal', 'modal-void-queue-document');
        $this->dispatch('loan-status-changed');
        $this->dispatch('notify', [
            'message' => $msg,
            'type' => $msgType
        ]);
        $this->reset(['voidDocId', 'voidDocNumber', 'voidReason']);
    }

    public function render()
    {
        $baseCountQuery = Document::where('status', 'waiting_for_file')->whereIn('type', ['outgoing', 'internal']);
        $countAll = (clone $baseCountQuery)->count();
        $countOutgoing = (clone $baseCountQuery)->where('type', 'outgoing')->count();
        $countInternal = (clone $baseCountQuery)->where('type', 'internal')->count();

        $query = Document::with(['documentType', 'classification', 'unit', 'createdBy', 'recipients.recipient'])
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

        $documents = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->paginate(10); // asc: yang paling lama antre didahulukan

        $availableFolders = $this->uploadDocTypeRaw
            ? \App\Models\Folder::forType($this->uploadDocTypeRaw)->with('parent')->orderBy('name')->get()
            : collect();

        $classifications = Classification::whereNotNull('parent_id')->orderBy('code')->get();
        $availableUnits = Unit::where('status', 'active')->orderBy('name')->get();

        return view('livewire.registry.scan-queue', [
            'documents' => $documents,
            'availableFolders' => $availableFolders,
            'classifications' => $classifications,
            'availableUnits' => $availableUnits,
            'countAll' => $countAll,
            'countOutgoing' => $countOutgoing,
            'countInternal' => $countInternal,
            'nasStatus' => $this->nasStatus,
        ])->layout('components.layouts.app', ['title' => 'Antrean Scan Tapersip']);
    }
}


