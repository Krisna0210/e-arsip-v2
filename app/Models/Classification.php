<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'subject',
        'parent_id',
        'active_years',
        'inactive_years',
        'outcome',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Classification::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Classification::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
