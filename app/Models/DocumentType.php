<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'default_sifat',
        'numbering_format',
        'starting_sequence',
        'starting_sequence_year',
    ];
}
