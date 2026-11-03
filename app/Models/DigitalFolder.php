<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalFolder extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'created_by',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DigitalFolder::class, 'parent_id');
    }

    public function getAncestorIds(): array
    {
        $ids = [];
        $curr = $this->parent;
        while ($curr) {
            $ids[] = $curr->id;
            $curr = $curr->parent;
        }
        return $ids;
    }

    public function children(): HasMany
    {
        return $this->hasMany(DigitalFolder::class, 'parent_id')->orderBy('name', 'asc');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DigitalDocument::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' / ' . $this->name;
        }

        return $this->name;
    }

    public function getTreeNameAttribute(): string
    {
        $depth = 0;
        $curr = $this->parent;
        while ($curr) {
            $depth++;
            $curr = $curr->parent;
        }

        return ($depth > 0 ? str_repeat('  ', $depth) . '↳ ' : '') . $this->name;
    }

    public static function getTree()
    {
        $roots = static::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $flattened = collect();

        $traverse = function ($folders) use (&$traverse, &$flattened) {
            foreach ($folders as $folder) {
                $flattened->push($folder);
                if ($folder->children && $folder->children->isNotEmpty()) {
                    $traverse($folder->children->sortBy('name'));
                }
            }
        };

        $traverse($roots);

        return $flattened;
    }

    /**
     * Get clean hierarchical directory path for physical storage (e.g. "Dokumen As-Built Drawing/Divisi Teknik/Tahun 2026")
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

        return !empty($segments) ? implode('/', $segments) : 'Root';
    }
}
