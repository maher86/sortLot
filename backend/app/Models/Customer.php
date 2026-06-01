<?php

namespace App\Models;

use App\Enums\VatType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'country',
        'emirate',
        'address',
        'vat_type',
        'trn',
        'credit_limit_fils',
        'payment_terms_days',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'vat_type' => VatType::class,
            'credit_limit_fils' => 'integer',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function wouldExceedCreditLimit(int $newInvoiceAmountFils, int $currentBalanceFils = 0): bool
    {
        if ($this->credit_limit_fils <= 0) {
            return false;
        }

        return ($currentBalanceFils + $newInvoiceAmountFils) > $this->credit_limit_fils;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
