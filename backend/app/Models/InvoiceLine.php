<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit_price_fils',
        'discount_pct',
        'line_total_fils',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceLine $line): void {
            $gross = (float) $line->quantity * (int) $line->unit_price_fils;
            $line->line_total_fils = max(0, (int) round($gross * (1 - ((float) $line->discount_pct / 100))));
        });

        static::saved(fn (InvoiceLine $line) => $line->invoice?->recalculate());
        static::deleted(fn (InvoiceLine $line) => $line->invoice?->recalculate());
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price_fils' => 'integer',
            'discount_pct' => 'decimal:2',
            'line_total_fils' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
