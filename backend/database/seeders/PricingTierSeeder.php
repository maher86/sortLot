<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingTierSeeder extends Seeder
{
    private const TIERS = [
        ['code' => '0', 'label' => 'Scrap', 'price_per_kg_fils' => 0, 'sort_order' => 0],
        ['code' => 'K1', 'label' => 'Grade 1', 'price_per_kg_fils' => 1200, 'sort_order' => 1],
        ['code' => 'K2', 'label' => 'Grade 2', 'price_per_kg_fils' => 1000, 'sort_order' => 2],
        ['code' => 'K3', 'label' => 'Grade 3', 'price_per_kg_fils' => 800, 'sort_order' => 3],
        ['code' => 'K4', 'label' => 'Grade 4', 'price_per_kg_fils' => 600, 'sort_order' => 4],
        ['code' => 'K5', 'label' => 'Grade 5', 'price_per_kg_fils' => 400, 'sort_order' => 5],
        ['code' => 'K1A', 'label' => 'Grade 1 Premium', 'price_per_kg_fils' => 1500, 'sort_order' => 6],
        ['code' => 'K2A', 'label' => 'Grade 2 Premium', 'price_per_kg_fils' => 1100, 'sort_order' => 7],
        ['code' => 'MIX', 'label' => 'Mixed Lot', 'price_per_kg_fils' => 700, 'sort_order' => 8],
        ['code' => 'FLAT', 'label' => 'Flat Price Item', 'price_flat_fils' => 500, 'sort_order' => 9],
    ];

    public function run(): void
    {
        $now = now();

        DB::table('pricing_tiers')->upsert(
            collect(self::TIERS)->map(fn (array $tier): array => [
                'code' => $tier['code'],
                'label' => $tier['label'],
                'price_per_kg_fils' => $tier['price_per_kg_fils'] ?? null,
                'price_flat_fils' => $tier['price_flat_fils'] ?? null,
                'description' => null,
                'is_active' => true,
                'sort_order' => $tier['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['code'],
            ['label', 'price_per_kg_fils', 'price_flat_fils', 'description', 'is_active', 'sort_order', 'updated_at'],
        );
    }
}
