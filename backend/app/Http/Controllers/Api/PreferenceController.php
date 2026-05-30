<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemType;
use App\Models\Preference;
use App\Models\PricingTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeView();

        $preferences = Cache::tags('preferences')->remember('preferences.flat', 300, function (): array {
            return Preference::query()
                ->orderBy('key')
                ->pluck('value', 'key')
                ->all();
        });

        return response()->json(['data' => $preferences]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $validated = collect($request->all())
            ->reject(fn (mixed $value): bool => is_array($value))
            ->all();

        abort_if(count($validated) !== count($request->all()), 422, 'Preference values must be scalar.');

        foreach ($validated as $key => $value) {
            Preference::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value === null ? null : (string) $value,
                    'group' => 'general',
                    'label' => Str::headline($key),
                ],
            );
        }

        $this->flushPreferenceCache();

        return $this->index();
    }

    public function pricingTiers(): JsonResponse
    {
        $this->authorizeView();

        return response()->json([
            'data' => PricingTier::query()->orderBy('sort_order')->orderBy('code')->get(),
        ]);
    }

    public function storePricingTier(Request $request): JsonResponse
    {
        $this->authorizeEdit('pricing_tiers.manage');

        $pricingTier = PricingTier::query()->create($request->validate($this->pricingTierRules()));
        $this->flushPreferenceCache();

        return response()->json(['data' => $pricingTier], 201);
    }

    public function updatePricingTier(Request $request, PricingTier $pricingTier): JsonResponse
    {
        $this->authorizeEdit('pricing_tiers.manage');

        $pricingTier->update($request->validate($this->pricingTierRules($pricingTier)));
        $this->flushPreferenceCache();

        return response()->json(['data' => $pricingTier->fresh()]);
    }

    public function destroyPricingTier(PricingTier $pricingTier): JsonResponse
    {
        $this->authorizeEdit('pricing_tiers.manage');

        $pricingTier->delete();
        $this->flushPreferenceCache();

        return response()->json(status: 204);
    }

    public function itemTypes(): JsonResponse
    {
        $this->authorizeView();

        return response()->json([
            'data' => ItemType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeItemType(Request $request): JsonResponse
    {
        $this->authorizeEdit('item_types.manage');

        $validated = $request->validate($this->itemTypeRules());
        $itemType = ItemType::query()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);
        $this->flushPreferenceCache();

        return response()->json(['data' => $itemType], 201);
    }

    public function updateItemType(Request $request, ItemType $itemType): JsonResponse
    {
        $this->authorizeEdit('item_types.manage');

        $validated = $request->validate($this->itemTypeRules($itemType));
        $itemType->update([
            ...$validated,
            'slug' => $validated['slug'] ?? ($validated['name'] ?? null ? Str::slug($validated['name']) : $itemType->slug),
        ]);
        $this->flushPreferenceCache();

        return response()->json(['data' => $itemType->fresh()]);
    }

    public function destroyItemType(ItemType $itemType): JsonResponse
    {
        $this->authorizeEdit('item_types.manage');

        $itemType->delete();
        $this->flushPreferenceCache();

        return response()->json(status: 204);
    }

    private function authorizeView(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(['super_admin', 'manager', 'viewer'])
                || request()->user()?->can('preferences.view'),
            403,
        );
    }

    private function authorizeEdit(?string $permission = null): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(['super_admin', 'manager'])
                || request()->user()?->can($permission ?? 'preferences.edit'),
            403,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingTierRules(?PricingTier $pricingTier = null): array
    {
        $required = $pricingTier ? 'sometimes' : 'required';

        return [
            'code' => [$required, 'string', 'max:10', Rule::unique('pricing_tiers', 'code')->ignore($pricingTier?->id)],
            'label' => [$required, 'string', 'max:50'],
            'price_per_kg_fils' => ['nullable', 'integer', 'min:0'],
            'price_flat_fils' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemTypeRules(?ItemType $itemType = null): array
    {
        $required = $itemType ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('item_types', 'slug')->ignore($itemType?->id)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    private function flushPreferenceCache(): void
    {
        Cache::tags('preferences')->flush();
    }
}
