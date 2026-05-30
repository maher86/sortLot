<?php

namespace Database\Seeders;

use App\Models\Preference;
use Illuminate\Database\Seeder;

class PreferenceSeeder extends Seeder
{
    private const PREFERENCES = [
        ['key' => 'company_name', 'value' => 'SortLot Trading', 'group' => 'company', 'label' => 'Company Name'],
        ['key' => 'company_trn', 'value' => null, 'group' => 'company', 'label' => 'Tax Registration Number'],
        ['key' => 'vat_rate_mainland', 'value' => '5.00', 'group' => 'vat', 'label' => 'Mainland VAT Rate'],
        ['key' => 'invoice_prefix_sales', 'value' => 'SO', 'group' => 'invoice', 'label' => 'Sales Invoice Prefix'],
        ['key' => 'invoice_prefix_purchase', 'value' => 'PO', 'group' => 'invoice', 'label' => 'Purchase Invoice Prefix'],
        ['key' => 'invoice_next_seq_sales', 'value' => '1', 'group' => 'invoice', 'label' => 'Next Sales Invoice Sequence'],
        ['key' => 'invoice_next_seq_purchase', 'value' => '1', 'group' => 'invoice', 'label' => 'Next Purchase Invoice Sequence'],
        ['key' => 'default_currency', 'value' => 'AED', 'group' => 'company', 'label' => 'Default Currency'],
        ['key' => 'payment_terms_days', 'value' => '30', 'group' => 'invoice', 'label' => 'Payment Terms Days'],
    ];

    public function run(): void
    {
        foreach (self::PREFERENCES as $preference) {
            Preference::query()->updateOrCreate(
                ['key' => $preference['key']],
                $preference,
            );
        }
    }
}
