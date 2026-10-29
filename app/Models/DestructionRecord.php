<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestructionRecord extends Model
{
    protected $fillable = [
        'batch_code',
        'document_id',
        'proposer_id',
        'approver_id',
        'executor_id',
        'status',
        'reason',
        'ba_file_path',
        'executed_at',
    ];

    protected static function booted()
    {
        static::creating(function ($record) {
            if (empty($record->batch_code)) {
                $record->batch_code = static::generateBatchCode();
            }
        });
    }

    public static function generateBatchCode(): string
    {
        $prefix = 'DUPA-' . now()->format('Ym') . '-';
        $latest = static::where('batch_code', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('batch_code');

        if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }
}
