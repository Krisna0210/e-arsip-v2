<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'folder_id',
        'document_type_id',
        'number',
        'sequence_number',
        'document_date',
        'received_date',
        'sender',
        'recipient_name',
        'signer_id',
        'signer_type',
        'subject',
        'unit_id',
        'classification_id',
        'sifat',
        'status',
        'archive_status',
        'current_version_id',
        'retention_start_date',
        'retention_end_date',
        'created_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'received_date' => 'date',
        'retention_start_date' => 'date',
        'retention_end_date' => 'date',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DocumentRecipient::class);
    }



    public function destructionRecords(): HasMany
    {
        return $this->hasMany(DestructionRecord::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class);
    }

    public function activeLoan()
    {
        return $this->hasOne(DocumentLoan::class)->whereIn('status', ['requested', 'borrowed'])->latestOfMany();
    }

    public function isCurrentlyBorrowed(): bool
    {
        return $this->loans()->where('status', 'borrowed')->exists();
    }

    /**
     * Generate standardized archive file name: {Nomor Surat}_{Tanggal Surat}.pdf
     * Slashes are replaced with spaces, illegal filesystem characters are removed.
     */
    public function generateArchiveFileName(string $extension = 'pdf', ?string $version = null): string
    {
        $cleanNumber = $this->number ? trim((string) $this->number) : 'DOKUMEN ' . $this->id;

        // Replace slashes with spaces
        $cleanNumber = str_replace(['/', '\\'], ' ', $cleanNumber);

        // Remove illegal filesystem characters (: * ? " < > |)
        $cleanNumber = preg_replace('/[:*?"<>|]/', '', $cleanNumber);

        // Collapse multiple spaces into a single space and trim
        $cleanNumber = trim(preg_replace('/\s+/', ' ', $cleanNumber));

        // Format document date as YYYY-MM-DD
        $dateStr = $this->document_date
            ? \Carbon\Carbon::parse($this->document_date)->format('Y-m-d')
            : date('Y-m-d');

        $baseName = "{$cleanNumber}_{$dateStr}";

        // If version is provided and greater than 1.0, append version suffix
        if ($version && !in_array((string) $version, ['1', '1.0', '1.0.0'])) {
            $cleanVersion = preg_replace('/[^0-9.]/', '', (string) $version);
            $baseName .= "_v{$cleanVersion}";
        }

        return "{$baseName}.{$extension}";
    }

    /**
     * Get storage directory path based on document type and storage locker hierarchy.
     * e.g. "documents/Surat Keluar/Tahun 2026/Divisi Keuangan" or "documents/Surat Masuk/Tanpa Loker"
     */
    public function getStorageDirectoryPath(): string
    {
        $typeFolder = match($this->type) {
            'incoming' => 'Surat Masuk',
            'outgoing' => 'Surat Keluar',
            'internal' => 'Surat Khusus',
            default => 'Umum',
        };

        // Ensure folder relation is evaluated accurately
        $folderPath = $this->folder
            ? $this->folder->getDirectoryPath()
            : 'Tanpa Loker';

        return "documents/{$typeFolder}/{$folderPath}";
    }

    /**
     * Relocate document to a new folder, syncing physical file on NAS storage.
     */
    public function relocateFolder(?int $newFolderId, ?int $userId = null): bool
    {
        $oldFolder = $this->folder;
        $oldFolderName = $oldFolder ? $oldFolder->name : 'Tanpa Loker';

        $newFolder = $newFolderId ? Folder::find($newFolderId) : null;
        $newFolderName = $newFolder ? $newFolder->name : 'Tanpa Loker';

        if ($this->folder_id == $newFolderId) {
            return true;
        }

        // Jika dokumen memiliki versi file fisik yang tersimpan
        $currentVersion = $this->currentVersion;
        if ($currentVersion && !empty($currentVersion->file_path)) {
            $oldFilePath = $currentVersion->file_path;
            $fileName = basename($oldFilePath);

            // Simulasikan folder_id baru untuk menghitung direktori tujuan
            $this->folder_id = $newFolderId;
            $this->setRelation('folder', $newFolder);
            $newDirectory = $this->getStorageDirectoryPath();
            $newFilePath = rtrim($newDirectory, '/') . '/' . $fileName;

            // Pindahkan file pada NAS (dengan fallback local storage jika ada)
            $nasDisk = Storage::disk('nas');
            $publicDisk = Storage::disk('public');

            $moved = false;

            if ($nasDisk->exists($oldFilePath)) {
                $nasDisk->makeDirectory($newDirectory);
                $nasDisk->move($oldFilePath, $newFilePath);
                $moved = true;
            } elseif ($publicDisk->exists($oldFilePath)) {
                $publicDisk->makeDirectory($newDirectory);
                $publicDisk->move($oldFilePath, $newFilePath);
                $moved = true;
            }

            // Perbarui path pada tabel document_versions
            if ($moved) {
                $currentVersion->update(['file_path' => $newFilePath]);
            }
        } else {
            $this->folder_id = $newFolderId;
        }

        $this->save();

        AuditLog::record(
            action: "Pemindahan Loker: " . ($this->number ?: $this->subject),
            documentId: $this->id,
            metadata: [
                'from_folder' => $oldFolderName,
                'to_folder' => $newFolderName,
                'old_folder_id' => $oldFolder?->id,
                'new_folder_id' => $newFolderId,
            ],
            userId: $userId ?: Auth::id()
        );

        return true;
    }
}


