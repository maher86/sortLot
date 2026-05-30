<?php

use App\Enums\VatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('contact_name', 150)->nullable();
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('country', 100);
            $table->text('address')->nullable();
            $table->enum('vat_type', array_column(VatType::cases(), 'value'))
                ->default(VatType::International->value);
            $table->string('trn', 50)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_swift', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('vat_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
