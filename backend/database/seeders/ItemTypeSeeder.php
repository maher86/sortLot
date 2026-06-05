<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTypeSeeder extends Seeder
{
    private const TYPES = [
        'Shirt',
        'T-Shirt',
        'Pants',
        'Jeans',
        'Shorts',
        'Skirt',
        'Dress',
        'Jacket',
        'Coat',
        'Sweater',
        'Hoodie',
        'Sportswear',
        'Kids Set',
        'Scarf',
        'Mixed Accessories',
    ];

    public function run(): void
    {
        $now = now();

        DB::table('item_types')->upsert(
            collect(self::TYPES)->map(fn (string $name, int $index): array => [
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['slug'],
            ['name', 'is_active', 'sort_order', 'updated_at'],
        );
    }
}
