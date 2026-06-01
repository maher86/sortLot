<?php

namespace App\Models;

use App\Enums\VatType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'country',
        'address',
        'vat_type',
        'trn',
        'bank_name',
        'bank_iban',
        'bank_swift',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'vat_type' => VatType::class,
            'is_active' => 'boolean',
        ];
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(Invoice::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
