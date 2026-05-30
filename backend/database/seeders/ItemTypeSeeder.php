<?php

namespace Database\Seeders;

use App\Models\ItemType;
use Illuminate\Database\Seeder;
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
        foreach (self::TYPES as $index => $name) {
            ItemType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index],
            );
        }
    }
}
