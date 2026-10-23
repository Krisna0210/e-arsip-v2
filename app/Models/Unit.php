<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'status',
    ];

    public function scopeDivisions($query)
    {
        return $query->where('type', 'division');
    }

    public function scopeDepartments($query)
    {
        return $query->where('type', 'department');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Resolve the parent division for this unit (or self if already a division).
     */
    public function getDivision(): ?Unit
    {
        if ($this->type === 'division') {
            return $this;
        }

        return $this->parent ? $this->parent->getDivision() : null;
    }

    /**
     * Get the division code for this unit.
     */
    public function getDivisionCode(): string
    {
        return $this->getDivision()?->code ?? $this->code;
    }
}
