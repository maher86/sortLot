<?php

namespace App\Http\Requests;

use App\Enums\VatType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
        $customer = $this->route('customer');
        $customerId = $customer?->id;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:100'],
            'emirate' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'vat_type' => ['sometimes', Rule::enum(VatType::class)],
            'trn' => ['nullable', 'string', 'max:50'],
            'credit_limit_fils' => ['sometimes', 'integer', 'min:0'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
