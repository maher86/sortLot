<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Enums\PackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Item;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function __construct(private readonly PackageService $packageService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Package::class);

        $packages = Package::query()
            ->withCount([
                'items',
                'items as available_items_count' => fn ($query) => $query->where('status', ItemStatus::Available->value),
            ])
            ->when($request->input('filter.status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('filter.supplier_id'), fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($request->input('search'), fn ($query, $search) => $query->where('reference', 'like', "%{$search}%"))
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return PackageResource::collection($packages);
    }

    public function store(PackageRequest $request): JsonResponse
    {
        $this->authorize('create', Package::class);

        $package = Package::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return PackageResource::make($this->packageWithCounts($package))->response()->setStatusCode(201);
    }

    public function show(Package $package): PackageResource
    {
        $this->authorize('view', $package);

        return PackageResource::make($this->packageWithCounts($package));
    }

    public function update(PackageRequest $request, Package $package): PackageResource
    {
        $this->authorize('update', $package);

        if (! $this->canMutatePackageDetails($package)) {
            abort(409, 'Package details can only be updated before sorting starts.');
        }

        $package->update($request->validated());

        return PackageResource::make($this->packageWithCounts($package));
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->authorize('delete', $package);

        if (! $this->canMutatePackageDetails($package) || $package->items()->exists()) {
            return response()->json([
                'message' => 'Package can only be deleted before sorting starts and before items are added.',
                'code' => 'PACKAGE_DELETE_NOT_ALLOWED',
            ], 409);
        }

        $package->delete();

        return response()->json(status: 204);
    }

    public function changeStatus(Request $request, Package $package): PackageResource
    {
        $this->authorize('changeStatus', $package);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(PackageStatus::class)],
        ]);

        $package = $this->packageService->changeStatus($package, PackageStatus::from($validated['status']), $request->user());

        return PackageResource::make($package);
    }

    public function bulkItems(Request $request, Package $package): JsonResponse
    {
        $this->authorize('createItems', $package);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.season' => ['required', Rule::enum(ItemSeason::class)],
            'items.*.gender' => ['required', Rule::enum(ItemGender::class)],
            'items.*.item_type_id' => ['required', 'integer', 'exists:item_types,id'],
            'items.*.pricing_tier_id' => ['required', 'integer', 'exists:pricing_tiers,id'],
            'items.*.condition_notes' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price_fils' => ['required', 'integer', 'min:0'],
            'items.*.barcode' => ['nullable', 'string', 'max:100', 'distinct', 'unique:items,barcode'],
        ]);

        $items = collect($validated['items'])->map(function (array $attributes) use ($package, $request): Item {
            return Item::query()->create([
                ...$attributes,
                'package_id' => $package->id,
                'status' => ItemStatus::Available,
                'sorted_by' => $request->user()->id,
            ]);
        });

        return response()->json([
            'data' => $items->map(fn (Item $item): array => [
                'id' => $item->id,
                'package_id' => $item->package_id,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'season' => $item->season->value,
                'gender' => $item->gender->value,
                'status' => $item->status->value,
            ])->values(),
        ], 201);
    }

    private function canMutatePackageDetails(Package $package): bool
    {
        return in_array($package->status, [
            PackageStatus::InTransit,
            PackageStatus::AtPort,
            PackageStatus::InCustoms,
            PackageStatus::InWarehouse,
        ], true);
    }

    private function packageWithCounts(Package $package): Package
    {
        return Package::query()
            ->withCount([
                'items',
                'items as available_items_count' => fn ($query) => $query->where('status', ItemStatus::Available->value),
            ])
            ->findOrFail($package->id);
    }
}
