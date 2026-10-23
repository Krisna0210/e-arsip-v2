<?php

namespace App\Livewire\Registry;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Notifications\DocumentArchivedNotification;
use App\Services\NasHealthService;
use Illuminate\Support\Facades\Log;
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

    public function render()
    {
        $baseCountQuery = Document::where('status', 'waiting_for_file')->whereIn('type', ['outgoing', 'internal']);
        $countAll = (clone $baseCountQuery)->count();
        $countOutgoing = (clone $baseCountQuery)->where('type', 'outgoing')->count();
        $countInternal = (clone $baseCountQuery)->where('type', 'internal')->count();

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

        $documents = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->paginate(10); // asc: yang paling lama antre didahulukan

        $availableFolders = $this->uploadDocTypeRaw
            ? \App\Models\Folder::forType($this->uploadDocTypeRaw)->with('parent')->orderBy('name')->get()
            : collect();

        return view('livewire.registry.scan-queue', [
            'documents' => $documents,
            'availableFolders' => $availableFolders,
            'countAll' => $countAll,
            'countOutgoing' => $countOutgoing,
            'countInternal' => $countInternal,
            'nasStatus' => $this->nasStatus,
        ])->layout('components.layouts.app', ['title' => 'Antrean Scan Tapersip']);
    }
}


