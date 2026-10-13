<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'description',
    ];

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get clean hierarchical directory path for physical storage (e.g. "Tahun 2026/Divisi Keuangan")
     */
    public function getDirectoryPath(): string
    {
        $segments = [];
        $current = $this;

        while ($current) {
            // Sanitize folder name for Linux/Windows filesystem
            $cleanName = str_replace(['/', '\\'], '-', trim($current->name));
            $cleanName = preg_replace('/[:*?"<>|]/', '', $cleanName);
            $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));

            if (!empty($cleanName)) {
                array_unshift($segments, $cleanName);
            }

            $current = $current->parent;
        }

        return !empty($segments) ? implode('/', $segments) : 'Tanpa Loker';
    }
}
