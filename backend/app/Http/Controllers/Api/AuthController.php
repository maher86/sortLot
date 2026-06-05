<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\Preference;
use App\Models\PricingTier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($this->isLocalSeededAdmin($credentials) && (
            ! $user
            || ! Hash::check($credentials['password'], $user->password)
            || ! $user->is_active
            || $this->localSeedDataIsMissing()
        )) {
            app(DatabaseSeeder::class)->run();
            $user = User::where('email', $credentials['email'])->first();
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return response()->json([
            'data' => [
                'token' => $user->createToken('api')->plainTextToken,
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    private function isLocalSeededAdmin(array $credentials): bool
    {
        return app()->environment('local')
            && $credentials['email'] === 'admin@sortlot.local'
            && $credentials['password'] === 'password';
    }

    private function localSeedDataIsMissing(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        $requiredPreferences = [
            'invoice_prefix_sales',
            'invoice_prefix_purchase',
            'invoice_next_seq_sales',
            'invoice_next_seq_purchase',
        ];

        return ! Role::query()->where('name', 'super_admin')->exists()
            || Preference::query()->whereIn('key', $requiredPreferences)->count() !== count($requiredPreferences)
            || ! ItemType::query()->exists()
            || ! PricingTier::query()->exists()
            || ! Customer::query()->where('email', 'customer-demo@sortlot.local')->exists()
            || ! Package::query()->where('reference', 'DEMO-BALE-001')->exists();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out.',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return response()->json([
            'data' => [
                'message' => 'Password updated.',
            ],
        ]);
    }
}
