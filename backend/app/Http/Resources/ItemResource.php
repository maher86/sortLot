<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_id' => $this->package_id,
            'package_reference' => $this->whenLoaded('package', fn (): ?string => $this->package?->reference),
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'season' => $this->season->value,
            'gender' => $this->gender->value,
            'item_type_id' => $this->item_type_id,
            'item_type' => $this->whenLoaded('itemType', fn (): ?string => $this->itemType?->name),
            'pricing_tier_id' => $this->pricing_tier_id,
            'pricing_tier' => $this->whenLoaded('pricingTier', fn (): ?string => $this->pricingTier?->code),
            'condition_notes' => $this->condition_notes,
            'status' => $this->status->value,
            'quantity' => $this->quantity,
            'weight_kg' => $this->weight_kg,
            'unit_price_fils' => $this->unit_price_fils,
            'sales_order_id' => $this->sales_order_id,
            'sorted_by' => $this->sorted_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
