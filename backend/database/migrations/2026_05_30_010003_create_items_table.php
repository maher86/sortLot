<?php

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->enum('season', array_column(ItemSeason::cases(), 'value'))->index();
            $table->enum('gender', array_column(ItemGender::cases(), 'value'))->index();
            $table->foreignId('item_type_id')->constrained('item_types')->restrictOnDelete();
            $table->foreignId('pricing_tier_id')->constrained('pricing_tiers')->restrictOnDelete();
            $table->text('condition_notes')->nullable();
            $table->enum('status', array_column(ItemStatus::cases(), 'value'))
                ->default(ItemStatus::Available->value)
                ->index();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedBigInteger('unit_price_fils');
            $table->ulid('sales_order_id')->nullable()->index();
            $table->foreignId('sorted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('barcode');
            $table->index('item_type_id');
            $table->index('pricing_tier_id');
            $table->index(['package_id', 'status']);
            $table->index(['season', 'gender', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
