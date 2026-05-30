<?php

namespace App\Events;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PackageStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Package $package,
        public PackageStatus $from,
        public PackageStatus $to,
        public User $user,
    ) {}
}
