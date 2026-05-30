<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Invoice extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected $fillable = [
        'type',
        'number',
        'reference',
        'status',
        'customer_id',
        'supplier_id',
        'related_invoice_id',
        'issue_date',
        'due_date',
        'delivery_date',
        'subtotal_fils',
        'discount_fils',
        'discount_pct',
        'vat_rate',
        'vat_amount_fils',
        'total_fils',
        'paid_amount_fils',
        'balance_fils',
        'currency',
        'exchange_rate',
        'notes',
        'internal_notes',
        'terms',
        'pdf_path',
        'pdf_generated_at',
        'sent_at',
        'paid_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'delivery_date' => 'date',
            'subtotal_fils' => 'integer',
            'discount_fils' => 'integer',
            'discount_pct' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount_fils' => 'integer',
            'total_fils' => 'integer',
            'paid_amount_fils' => 'integer',
            'balance_fils' => 'integer',
            'exchange_rate' => 'decimal:6',
            'pdf_generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculate(): void
    {
        $subtotalFils = (int) $this->lines()->sum('line_total_fils');
        $discountFils = $this->discount_pct > 0
            ? (int) round($subtotalFils * ((float) $this->discount_pct / 100))
            : min((int) $this->discount_fils, $subtotalFils);
        $taxableFils = max(0, $subtotalFils - $discountFils);
        $vatAmountFils = (int) round($taxableFils * ((float) $this->vat_rate / 100));
        $totalFils = $taxableFils + $vatAmountFils;
        $paidAmountFils = (int) $this->payments()->sum('amount_fils');

        $this->forceFill([
            'subtotal_fils' => $subtotalFils,
            'discount_fils' => $discountFils,
            'vat_amount_fils' => $vatAmountFils,
            'total_fils' => $totalFils,
            'paid_amount_fils' => $paidAmountFils,
            'balance_fils' => max(0, $totalFils - $paidAmountFils),
            'paid_at' => $totalFils > 0 && $paidAmountFils >= $totalFils ? now() : null,
        ]);

        $this->applyPaymentStatus();
        $this->saveQuietly();
    }

    public function updatePaidAmount(): void
    {
        $this->recalculate();
    }

    public function transitionTo(InvoiceStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        if (! $this->status->canTransitionTo($status)) {
            throw new InvalidArgumentException("Cannot transition invoice from {$this->status->value} to {$status->value}.");
        }

        $this->forceFill(['status' => $status])->save();
    }

    public function markOverdue(): void
    {
        if (! in_array($this->status, [InvoiceStatus::Pending, InvoiceStatus::Partial], true)) {
            return;
        }

        if ($this->due_date?->isPast() && $this->balance_fils > 0) {
            $this->transitionTo(InvoiceStatus::Overdue);
        }
    }

    public function scopeType(Builder $query, InvoiceType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    private function applyPaymentStatus(): void
    {
        if (! in_array($this->status, [InvoiceStatus::Pending, InvoiceStatus::Partial, InvoiceStatus::Overdue, InvoiceStatus::Paid], true)) {
            return;
        }

        if ($this->paid_amount_fils <= 0) {
            $this->status = $this->due_date?->isPast() ? InvoiceStatus::Overdue : InvoiceStatus::Pending;

            return;
        }

        $this->status = $this->paid_amount_fils >= $this->total_fils
            ? InvoiceStatus::Paid
            : InvoiceStatus::Partial;
    }
}
