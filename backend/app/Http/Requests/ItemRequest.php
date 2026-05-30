<?php

namespace App\Http\Requests;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $item = $this->route('item');
        $itemId = $item?->id;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'package_id' => [$required, 'ulid', 'exists:packages,id'],
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('items', 'sku')->ignore($itemId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->ignore($itemId)],
            'season' => [$required, Rule::enum(ItemSeason::class)],
            'gender' => [$required, Rule::enum(ItemGender::class)],
            'item_type_id' => [$required, 'integer', 'exists:item_types,id'],
            'pricing_tier_id' => [$required, 'integer', 'exists:pricing_tiers,id'],
            'condition_notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ItemStatus::class)],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'unit_price_fils' => [$required, 'integer', 'min:0'],
            'sales_order_id' => ['nullable', 'ulid'],
            'sorted_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
