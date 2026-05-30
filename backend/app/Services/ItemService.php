<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;

class ItemService
{
    public function changeStatus(Item $item, ItemStatus $target, User $user, string $reason, ?Request $request = null): Item
    {
        $from = $item->status;

        $item->forceFill([
            'status' => $target,
            'sorted_by' => $user->id,
        ])->save();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'status_changed',
            'model_type' => Item::class,
            'model_id' => $item->id,
            'old_values' => ['status' => $from->value],
            'new_values' => [
                'status' => $target->value,
                'reason' => $reason,
            ],
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        return $item->fresh(['package', 'itemType', 'pricingTier', 'sortedBy']);
    }
}
