<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Register ─────────────────────────────────────────────────────────────

    public function test_register_with_email_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'identifier' => 'test@example.com',
            'password'   => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'uuid', 'email', 'role', 'access_token', 'token_type', 'expires_in'],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('wallets', []);
        $this->assertCount(1, Wallet::all());
    }

    public function test_register_with_phone_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'identifier' => '+201012345678',
            'password'   => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['phone' => '+201012345678']);
    }

    public function test_register_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'identifier' => 'test@example.com',
            'password'   => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_missing_identifier_returns_422(): void
    {
        $this->postJson('/api/v1/auth/register', ['password' => 'password123'])
            ->assertStatus(422);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function test_login_with_email_returns_token(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'login@example.com',
            'password'   => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_login_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'login@example.com',
            'password'   => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_login_nonexistent_user_returns_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'nobody@example.com',
            'password'   => 'password123',
        ])->assertStatus(401);
    }

    // ─── Me ───────────────────────────────────────────────────────────────────

    public function test_me_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_with_valid_token_returns_user(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $token = auth('api')->login($user);

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['email' => 'me@example.com']]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function test_logout_returns_success(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Logged out successfully.']);
    }

    // ─── Google Auth ──────────────────────────────────────────────────────────

    public function test_google_auth_creates_new_user(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'sub'   => 'google-uid-123',
                'email' => 'google@example.com',
                'name'  => 'Test User',
                'aud'   => '',
                'azp'   => '',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'google_id_token' => 'fake-google-token',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['access_token', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'google@example.com']);
        $this->assertDatabaseHas('social_accounts', [
            'provider'    => 'google',
            'provider_id' => 'google-uid-123',
        ]);
    }

    public function test_google_auth_logs_in_existing_user(): void
    {
        // Existing social account
        $user = User::factory()->create(['email' => 'existing@example.com']);
        \DB::table('social_accounts')->insert([
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'google-uid-existing',
            'created_at'  => now(),
        ]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'sub'   => 'google-uid-existing',
                'email' => 'existing@example.com',
                'name'  => 'Existing User',
                'aud'   => '',
                'azp'   => '',
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/google', [
            'google_id_token' => 'fake-google-token',
        ])->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(1, User::count()); // No duplicate user created
    }

    public function test_google_auth_with_invalid_token_returns_422(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_token', 'error_description' => 'Token has been expired or revoked.'], 400),
        ]);

        $this->postJson('/api/v1/auth/google', [
            'google_id_token' => 'invalid-token',
        ])->assertStatus(422);
    }
}
