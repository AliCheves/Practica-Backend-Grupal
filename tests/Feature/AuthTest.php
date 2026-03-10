<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test it can login successfully and issue an API token.
     */
    public function testItCanLoginSuccessfully(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /**
     * Test it returns the authenticated user information using a bearer token.
     */
    public function testItCanReturnAuthenticatedUserInformation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/profile');

        $response->assertOk()
            ->assertJson([
                'profile' => [
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * Test it can logout successfully and invalidate the issued token.
     */
    public function testItCanAllowSuccessfullyLogout(): void
    {
        $user = User::factory()->create();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('token');

        $logoutResponse = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseEmpty('personal_access_tokens');

        app('auth')->forgetGuards();

        $this->flushHeaders()
            ->withToken($token)
            ->getJson('/api/v1/auth/profile')
            ->assertUnauthorized();
    }

    /**
     * Test protected endpoints reject unauthenticated requests.
     */
    public function testProtectedEndpointsRequireAuthentication(): void
    {
        $this->getJson('/api/v1/auth/profile')
            ->assertUnauthorized();
    }
}
