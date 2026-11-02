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
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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

    /**
     * Get folders tree flattened with hierarchical order and indentation arrow
     */
    public static function getTreeForType(string $type)
    {
        $roots = static::forType($type)
            ->whereNull('parent_id')
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
