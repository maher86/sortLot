<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Events\PackageStatusChanged;
use App\Models\Package;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function changeStatus(Package $package, PackageStatus $target, User $user): Package
    {
        $current = $package->status;

        if (! $current->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition package from {$current->value} to {$target->value}."],
            ]);
        }

        $attributes = ['status' => $target];

        if ($target === PackageStatus::Sorting && ! $package->sorting_started_at) {
            $attributes['sorting_started_at'] = now();
            $attributes['sorted_by'] = $user->id;
        }

        if ($target === PackageStatus::Sorted && ! $package->sorting_completed_at) {
            $attributes['sorting_completed_at'] = now();
            $attributes['sorted_by'] = $user->id;
        }

        $package->forceFill($attributes)->save();

        PackageStatusChanged::dispatch($package->fresh(), $current, $target, $user);

        return $package->fresh();
    }
}
