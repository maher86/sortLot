<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->enum('type', array_column(InvoiceType::cases(), 'value'))->index();
            $table->string('number', 50)->unique();
            $table->string('reference', 100)->nullable();
            $table->enum('status', array_column(InvoiceStatus::cases(), 'value'))
                ->default(InvoiceStatus::Draft->value)
                ->index();
            $table->foreignUlid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUlid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignUlid('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date')->nullable()->index();
            $table->date('delivery_date')->nullable();
            $table->unsignedBigInteger('subtotal_fils')->default(0);
            $table->unsignedBigInteger('discount_fils')->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('vat_amount_fils')->default(0);
            $table->unsignedBigInteger('total_fils')->default(0);
            $table->unsignedBigInteger('paid_amount_fils')->default(0);
            $table->unsignedBigInteger('balance_fils')->default(0);
            $table->char('currency', 3)->default('AED');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('issue_date');
            $table->index('number');
            $table->index(['type', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index(['type', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
