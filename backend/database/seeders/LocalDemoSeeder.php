<?php

namespace Database\Seeders;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Enums\PackageStatus;
use App\Enums\VatType;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package as SortLotPackage;
use App\Models\PricingTier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@sortlot.local')->firstOrFail();
        $supplier = Supplier::query()->updateOrCreate(
            ['email' => 'supplier-demo@sortlot.local'],
            [
                'name' => 'Demo Supplier',
                'contact_name' => 'Operations Desk',
                'country' => 'AE',
                'vat_type' => VatType::FreeZone,
                'is_active' => true,
            ],
        );

        Customer::query()->updateOrCreate(
            ['email' => 'customer-demo@sortlot.local'],
            [
                'name' => 'Demo Customer',
                'contact_name' => 'Buying Desk',
                'country' => 'AE',
                'vat_type' => VatType::FreeZone,
                'is_active' => true,
            ],
        );

        $package = SortLotPackage::query()->updateOrCreate(
            ['reference' => 'DEMO-BALE-001'],
            [
                'supplier_id' => $supplier->id,
                'origin_country' => 'UAE',
                'destination_country' => 'UAE',
                'status' => PackageStatus::Sorting,
                'weight_kg' => 420,
                'number_of_bags' => 28,
                'notes' => 'Local demo package for smoke testing.',
                'created_by' => $admin->id,
                'sorted_by' => $admin->id,
                'arrived_at' => now()->subDays(2),
                'sorting_started_at' => now()->subDay(),
            ],
        );

        $shirt = ItemType::query()->where('slug', 'shirt')->firstOrFail();
        $jeans = ItemType::query()->where('slug', 'jeans')->firstOrFail();
        $tierOne = PricingTier::query()->where('code', 'K1')->firstOrFail();
        $tierTwo = PricingTier::query()->where('code', 'K2')->firstOrFail();

        $items = [
            ['barcode' => 'DEMO-ITEM-001', 'item_type_id' => $shirt->id, 'pricing_tier_id' => $tierOne->id, 'gender' => ItemGender::Man, 'season' => ItemSeason::Summer, 'unit_price_fils' => 1800],
            ['barcode' => 'DEMO-ITEM-002', 'item_type_id' => $jeans->id, 'pricing_tier_id' => $tierTwo->id, 'gender' => ItemGender::Woman, 'season' => ItemSeason::General, 'unit_price_fils' => 2200],
            ['barcode' => 'DEMO-ITEM-003', 'item_type_id' => $shirt->id, 'pricing_tier_id' => $tierOne->id, 'gender' => ItemGender::Boy, 'season' => ItemSeason::Spring, 'unit_price_fils' => 1200],
        ];

        foreach ($items as $attributes) {
            Item::query()->updateOrCreate(
                ['barcode' => $attributes['barcode']],
                [
                    ...$attributes,
                    'package_id' => $package->id,
                    'status' => ItemStatus::Available,
                    'quantity' => 1,
                    'sorted_by' => $admin->id,
                ],
            );
        }
    }
}
