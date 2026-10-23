<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'default_sifat',
        'numbering_format',
        'starting_sequence',
        'starting_sequence_dir',
        'starting_sequence_year',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
