<?php

namespace App\Http\Controllers\Api;

use App\Enums\VatType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($request->boolean('with_trashed'), fn ($query) => $query->withTrashed())
            ->when($request->input('filter.vat_type'), fn ($query, $vatType) => $query->where('vat_type', $vatType))
            ->when($request->has('filter.is_active'), fn ($query) => $query->where('is_active', $request->boolean('filter.is_active')))
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('trn', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return CustomerResource::collection($customers);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::query()->create($request->validated());

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        return CustomerResource::make($customer);
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return CustomerResource::make($customer->fresh());
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(status: 204);
    }

    public function restore(string $customer): CustomerResource
    {
        $customer = Customer::withTrashed()->findOrFail($customer);

        $this->authorize('restore', $customer);

        $customer->restore();

        return CustomerResource::make($customer->fresh());
    }

    public function statement(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'projected_invoice_fils' => ['nullable', 'integer', 'min:0'],
            'vat_type' => ['nullable', Rule::enum(VatType::class)],
        ]);

        $invoices = collect();
        $balanceFils = 0;

        if (Schema::hasTable('invoices')) {
            $query = $customer->salesOrders()
                ->when(isset($validated['from']), fn ($query) => $query->whereDate('issue_date', '>=', $validated['from']))
                ->when(isset($validated['to']), fn ($query) => $query->whereDate('issue_date', '<=', $validated['to']))
                ->latest('issue_date');

            $invoices = $query->get();
            $balanceFils = (int) $query->cloneWithout(['orders'])->sum('balance_fils');
        }

        $projectedInvoiceFils = (int) ($validated['projected_invoice_fils'] ?? 0);

        return response()->json([
            'data' => [
                'customer' => CustomerResource::make($customer),
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'balance_fils' => $balanceFils,
                'credit_limit_fils' => $customer->credit_limit_fils,
                'available_credit_fils' => max(0, $customer->credit_limit_fils - $balanceFils),
                'projected_invoice_fils' => $projectedInvoiceFils,
                'would_exceed_credit_limit' => $customer->wouldExceedCreditLimit($projectedInvoiceFils, $balanceFils),
                'invoices' => $invoices->values(),
            ],
        ]);
    }

    public function salesOrders(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json([
            'data' => Schema::hasTable('invoices')
                ? $customer->salesOrders()->latest('issue_date')->paginate(15)
                : [],
        ]);
    }
}
