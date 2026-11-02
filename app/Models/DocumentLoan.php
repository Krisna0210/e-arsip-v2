<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_code',
        'document_id',
        'user_id',
        'officer_id',
        'borrow_date',
        'expected_return_date',
        'returned_date',
        'purpose',
        'notes',
        'status',
        'loan_type',
        'duration_hours',
        'access_expires_at',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
        'access_expires_at' => 'datetime',
    ];

    public function isSoftcopy(): bool
    {
        return in_array($this->loan_type, ['softcopy', 'both']);
    }

    public function isHardcopy(): bool
    {
        return in_array($this->loan_type, ['hardcopy', 'both']);
    }

    public function hasActiveSoftcopyAccess(): bool
    {
        return $this->isSoftcopy()
            && $this->status === 'borrowed'
            && $this->access_expires_at
            && now()->lte($this->access_expires_at);
    }

    public function getRemainingTimeHumanAttribute(): string
    {
        if (!$this->access_expires_at) {
            return '-';
        }

        if (now()->gt($this->access_expires_at)) {
            return 'Kedaluwarsa';
        }

        $diff = now()->diff($this->access_expires_at);
        if ($diff->days > 0) {
            return "{$diff->days} hari {$diff->h} jam";
        }
        if ($diff->h > 0) {
            return "{$diff->h} jam {$diff->i} mnt";
        }
        return "{$diff->i} menit";
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['requested', 'borrowed']);
    }

    /**
     * Generate unique loan ticket code: TIKET-YYYYMM-XXXX
     */
    public static function generateTicketCode(): string
    {
        $prefix = 'TIKET-' . date('Ym') . '-';
        $latest = static::where('loan_code', 'like', $prefix . '%')
            ->withTrashed()
            ->latest('id')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->loan_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
