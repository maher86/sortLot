<?php

namespace App\Http\Resources;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Package */
class PackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'supplier_id' => $this->supplier_id,
            'purchase_order_id' => $this->purchase_order_id,
            'origin_country' => $this->origin_country,
            'destination_country' => $this->destination_country,
            'status' => $this->status->value,
            'weight_kg' => $this->weight_kg,
            'number_of_bags' => $this->number_of_bags,
            'notes' => $this->notes,
            'arrived_at' => $this->arrived_at?->toISOString(),
            'sorting_started_at' => $this->sorting_started_at?->toISOString(),
            'sorting_completed_at' => $this->sorting_completed_at?->toISOString(),
            'sorted_by' => $this->sorted_by,
            'created_by' => $this->created_by,
            'items_count' => $this->items_count,
            'available_items_count' => $this->available_items_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
