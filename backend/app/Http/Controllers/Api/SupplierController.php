<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Schema;

class SupplierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->boolean('with_trashed'), fn ($query) => $query->withTrashed())
            ->when($request->has('filter.is_active'), fn ($query) => $query->where('is_active', $request->boolean('filter.is_active')))
            ->when($request->input('filter.vat_type'), fn ($query, $vatType) => $query->where('vat_type', $vatType))
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

        return SupplierResource::collection($suppliers);
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::query()->create($request->validated());

        return SupplierResource::make($supplier)->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);

        return SupplierResource::make($supplier);
    }

    public function update(SupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return SupplierResource::make($supplier->fresh());
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return response()->json(status: 204);
    }

    public function restore(string $supplier): SupplierResource
    {
        $supplier = Supplier::withTrashed()->findOrFail($supplier);

        $this->authorize('restore', $supplier);

        $supplier->restore();

        return SupplierResource::make($supplier->fresh());
    }

    public function packages(Supplier $supplier): AnonymousResourceCollection
    {
        $this->authorize('view', $supplier);

        return PackageResource::collection($supplier->packages()->latest()->paginate(15));
    }

    public function purchaseOrders(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json([
            'data' => Schema::hasTable('invoices')
                ? $supplier->purchaseOrders()->latest('issue_date')->paginate(15)
                : [],
        ]);
    }
}
