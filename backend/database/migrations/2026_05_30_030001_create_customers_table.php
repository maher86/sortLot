<?php

use App\Enums\VatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('contact_name', 150)->nullable();
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('country', 100)->default('AE');
            $table->string('emirate', 100)->nullable();
            $table->text('address')->nullable();
            $table->enum('vat_type', array_column(VatType::cases(), 'value'))
                ->default(VatType::Mainland->value);
            $table->string('trn', 50)->nullable();
            $table->unsignedBigInteger('credit_limit_fils')->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
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
        Schema::dropIfExists('customers');
    }
};
