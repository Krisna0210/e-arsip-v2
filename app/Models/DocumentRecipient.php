<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentRecipient extends Model
{
    protected $fillable = [
        'document_id',
        'recipient_id',
        'recipient_type',
        'receipt_status',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
