<?php

use App\Enums\PackageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('reference', 50)->unique();
            $table->ulid('supplier_id')->nullable()->index();
            $table->ulid('purchase_order_id')->nullable()->index();
            $table->string('origin_country', 100);
            $table->string('destination_country', 100)->default('AE');
            $table->enum('status', array_column(PackageStatus::cases(), 'value'))
                ->default(PackageStatus::InTransit->value)
                ->index();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->unsignedSmallInteger('number_of_bags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('sorting_started_at')->nullable();
            $table->timestamp('sorting_completed_at')->nullable();
            $table->foreignId('sorted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
