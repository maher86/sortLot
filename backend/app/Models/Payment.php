<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'amount_fils',
        'payment_method',
        'payment_date',
        'reference',
        'bank_name',
        'notes',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Payment $payment) => $payment->invoice?->updatePaidAmount());
        static::deleted(fn (Payment $payment) => $payment->invoice?->updatePaidAmount());
    }

    protected function casts(): array
    {
        return [
            'amount_fils' => 'integer',
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
