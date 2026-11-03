<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalDocument extends Model
{
    use SoftDeletes;

    const CATEGORIES = [
        'As-Built Drawing',
        'Kontrak/Perjanjian',
        'Dokumen Hukum/Aset',
        'Laporan Proyek',
        'Manual/Pedoman'
    ];

    protected $fillable = [
        'title',
        'document_number',
        'category',
        'access_type',
        'year',
        'unit_id',
        'tags',
        'description',
        'folder_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'file_size' => 'integer',
    ];

    public static function generateDocumentNumber(?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $suffix = "-ARSIP-DIG-{$year}";

        $latest = static::withTrashed()
            ->where('document_number', 'like', "%{$suffix}")
            ->orderBy('id', 'desc')
            ->value('document_number');

        $next = 1;
        if ($latest && preg_match('/^(\d+)-ARSIP-DIG-/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT) . $suffix;
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DigitalFolder::class, 'folder_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DigitalDocumentAccessRequest::class, 'digital_document_id');
    }

    public function hasActiveAccessForUser(?int $userId): bool
    {
        if ($this->access_type === 'public') {
            return true;
        }

        if (!$userId) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isTapersip() || $this->created_by === $user->id) {
            return true;
        }

        return $this->accessRequests()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function getActiveAccessForUser(?int $userId): ?DigitalDocumentAccessRequest
    {
        if (!$userId) return null;

        return $this->accessRequests()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function getPendingAccessForUser(?int $userId): ?DigitalDocumentAccessRequest
    {
        if (!$userId) return null;

        return $this->accessRequests()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    public function getSystemFileNameAttribute(): string
    {
        $base = basename($this->file_path);
        if (!empty($this->document_number) && str_starts_with($base, $this->document_number)) {
            return $base;
        }

        $ext = pathinfo($this->file_path ?: $this->file_name, PATHINFO_EXTENSION) ?: 'pdf';
        $cleanTitle = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($this->title, 50, ''));
        return "{$this->document_number}_{$cleanTitle}.{$ext}";
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (float) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getTagsListAttribute(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        // Parse tags split by space, comma, or newline
        preg_match_all('/#?([\w\-]+)/u', $this->tags, $matches);
        return array_unique($matches[0] ?? []);
    }

    /**
     * Get clean hierarchical directory path for physical storage on NAS (e.g. "library/Dokumen As-Built Drawing/Divisi Teknik/Tahun 2026")
     */
    public function getStorageDirectoryPath(): string
    {
        $folderPath = $this->folder
            ? $this->folder->getDirectoryPath()
            : 'Root';

        return "library/{$folderPath}";
    }

    /**
     * Relocate document to a new folder, syncing physical file on NAS storage.
     */
    public function relocateFolder(?int $newFolderId): bool
    {
        if ($this->folder_id == $newFolderId) {
            return true;
        }

        $newFolder = $newFolderId ? DigitalFolder::find($newFolderId) : null;

        if (!empty($this->file_path)) {
            $oldFilePath = $this->file_path;
            $fileName = basename($oldFilePath);

            // Simulasikan folder_id baru untuk menghitung direktori tujuan
            $this->folder_id = $newFolderId;
            $this->setRelation('folder', $newFolder);
            $newDirectory = $this->getStorageDirectoryPath();
            $newFilePath = rtrim($newDirectory, '/') . '/' . $fileName;

            // Pindahkan file pada NAS (dengan fallback local storage jika ada)
            $nasDisk = \Illuminate\Support\Facades\Storage::disk('nas');
            $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');

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

            if ($moved) {
                $this->file_path = $newFilePath;
            }
        } else {
            $this->folder_id = $newFolderId;
        }

        return $this->save();
    }
}
