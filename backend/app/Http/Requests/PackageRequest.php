<?php

namespace App\Http\Requests;

use App\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageRequest extends FormRequest
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
        $package = $this->route('package');
        $packageId = $package?->id;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'reference' => [$required, 'string', 'max:50', Rule::unique('packages', 'reference')->ignore($packageId)],
            'supplier_id' => ['nullable', 'ulid'],
            'purchase_order_id' => ['nullable', 'ulid'],
            'origin_country' => [$required, 'string', 'max:100'],
            'destination_country' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(PackageStatus::class)],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'number_of_bags' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'notes' => ['nullable', 'string'],
            'arrived_at' => ['nullable', 'date'],
            'sorting_started_at' => ['nullable', 'date'],
            'sorting_completed_at' => ['nullable', 'date'],
            'sorted_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
