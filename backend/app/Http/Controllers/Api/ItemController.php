<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function __construct(private readonly ItemService $itemService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Item::class);

        $items = Item::query()
            ->with(['package', 'itemType', 'pricingTier', 'sortedBy'])
            ->when($request->input('filter.season'), fn ($query, $season) => $query->where('season', $season))
            ->when($request->input('filter.gender'), fn ($query, $gender) => $query->where('gender', $gender))
            ->when($request->input('filter.item_type_id'), fn ($query, $itemTypeId) => $query->where('item_type_id', $itemTypeId))
            ->when($request->input('filter.type'), fn ($query, $itemTypeId) => $query->where('item_type_id', $itemTypeId))
            ->when($request->input('filter.pricing_tier_id'), fn ($query, $pricingTierId) => $query->where('pricing_tier_id', $pricingTierId))
            ->when($request->input('filter.package_id'), fn ($query, $packageId) => $query->where('package_id', $packageId))
            ->when($request->input('filter.status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('search'), fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->cursorPaginate((int) $request->integer('per_page', 15));

        return ItemResource::collection($items);
    }

    public function store(ItemRequest $request): JsonResponse
    {
        $this->authorize('create', Item::class);

        $item = Item::query()->create([
            ...$request->validated(),
            'sorted_by' => $request->validated('sorted_by') ?? $request->user()->id,
        ]);

        return ItemResource::make($item->fresh(['package', 'itemType', 'pricingTier', 'sortedBy']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Item $item): ItemResource
    {
        $this->authorize('view', $item);

        return ItemResource::make($item->load(['package', 'itemType', 'pricingTier', 'sortedBy']));
    }

    public function update(ItemRequest $request, Item $item): ItemResource
    {
        $this->authorize('update', $item);

        $item->update($request->validated());

        return ItemResource::make($item->fresh(['package', 'itemType', 'pricingTier', 'sortedBy']));
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return response()->json(status: 204);
    }

    public function findBySku(string $sku): ItemResource
    {
        $this->authorize('viewAny', Item::class);

        $item = Item::query()
            ->with(['package', 'itemType', 'pricingTier', 'sortedBy'])
            ->where('sku', $sku)
            ->firstOrFail();

        return ItemResource::make($item);
    }

    public function findByBarcode(string $barcode): ItemResource
    {
        $this->authorize('viewAny', Item::class);

        $item = Item::query()
            ->with(['package', 'itemType', 'pricingTier', 'sortedBy'])
            ->where('barcode', $barcode)
            ->firstOrFail();

        return ItemResource::make($item);
    }

    public function changeStatus(Request $request, Item $item): ItemResource
    {
        $this->authorize('changeStatus', $item);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ItemStatus::class)],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $item = $this->itemService->changeStatus(
            $item,
            ItemStatus::from($validated['status']),
            $request->user(),
            $validated['reason'],
            $request,
        );

        return ItemResource::make($item);
    }
}
