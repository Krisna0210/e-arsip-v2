<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalDocumentAccessRequest extends Model
{
    protected $fillable = [
        'digital_document_id',
        'user_id',
        'reason',
        'status',
        'duration_hours',
        'expires_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DigitalDocument::class, 'digital_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'approved' && $this->expires_at && $this->expires_at->isFuture();
    }

    public function getRemainingTimeHumanAttribute(): string
    {
        if (!$this->isActive()) {
            return 'Kedaluwarsa';
        }

        return 'Sisa ' . now()->diffForHumans($this->expires_at, [
            'parts' => 2,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }
}
