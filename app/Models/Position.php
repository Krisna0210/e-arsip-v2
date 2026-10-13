<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'level',
        'authority_config',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
