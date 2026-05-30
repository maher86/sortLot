<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@sortlot.local',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'roles', 'permissions'],
                ],
            ])
            ->assertJsonPath('data.user.email', 'admin@sortlot.local');
    }

    public function test_wrong_password_returns_422(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@sortlot.local',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_rate_limit_on_login(): void
    {
        $this->seed();
        $ip = '10.10.'.random_int(1, 250).'.'.random_int(1, 250);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/v1/auth/login', [
                'email' => 'admin@sortlot.local',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/v1/auth/login', [
            'email' => 'admin@sortlot.local',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_authenticated_user_can_get_me(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'admin@sortlot.local')
            ->assertJsonPath('data.user.roles.0', 'super_admin');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
