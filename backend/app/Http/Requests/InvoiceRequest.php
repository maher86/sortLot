<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'customer_id' => ['nullable', 'ulid', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'ulid', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'issue_date' => [$required, 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'delivery_date' => ['nullable', 'date'],
            'discount_fils' => ['sometimes', 'integer', 'min:0'],
            'discount_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'exchange_rate' => ['sometimes', 'numeric', 'min:0.000001'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => [$this->isMethod('post') ? 'required' : 'sometimes', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'ulid', 'exists:items,id'],
            'lines.*.description' => ['required_without:lines.*.item_id', 'string', 'max:255'],
            'lines.*.quantity' => ['sometimes', 'numeric', 'min:0.001'],
            'lines.*.unit_price_fils' => ['required_without:lines.*.item_id', 'integer', 'min:0'],
            'lines.*.discount_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'lines.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
