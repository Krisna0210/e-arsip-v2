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
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
    ];

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
