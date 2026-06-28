<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\UnauthorizedException;
use App\Models\User;
use App\Modules\Auth\DTOs\GoogleAuthDTO;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class AuthService
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    // ─── Register ─────────────────────────────────────────────────────────────

    /**
     * Register a new user and return a token immediately (no separate login step).
     *
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    public function register(RegisterDTO $dto): array
    {
        [$field, $value] = $this->resolveIdentifier($dto->identifier);

        // Check uniqueness before creating
        if (User::where($field, $value)->exists()) {
            throw new BusinessException(
                $field === 'email'
                    ? 'An account with this email already exists.'
                    : 'An account with this phone number already exists.'
            );
        }

        $user = DB::transaction(function () use ($dto, $field, $value): User {
            $user = User::create([
                'uuid'     => Str::uuid()->toString(),
                $field     => $value,
                'password' => $dto->password, // cast 'hashed' handles bcrypt
                'username' => $dto->username,
                'role'     => 'regular',
                'status'   => 'active',
            ]);

            // Every user gets a wallet immediately (required for coin operations)
            $user->wallet()->create([
                'available_balance' => 0,
                'held_balance'      => 0,
                'total_earned'      => 0,
                'total_spent'       => 0,
            ]);

            return $user;
        });

        Log::info('New user registered', ['user_id' => $user->id, 'method' => $field]);

        return $this->buildTokenResponse($user);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    /**
     * Authenticate a user by identifier (email or phone) and password.
     *
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    public function login(LoginDTO $dto): array
    {
        [$field, $value] = $this->resolveIdentifier($dto->identifier);

        $user = User::where($field, $value)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new UnauthorizedException('Invalid credentials.');
        }

        if ($user->is_banned) {
            throw new UnauthorizedException('Your account has been suspended.');
        }

        if ($user->status === 'suspended') {
            throw new UnauthorizedException('Your account has been suspended.');
        }

        $user->update(['last_active_at' => now()]);

        return $this->buildTokenResponse($user);
    }

    // ─── Google Sign-In ───────────────────────────────────────────────────────

    /**
     * Sign in with Google — login or register in a single call.
     *
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    public function googleAuth(GoogleAuthDTO $dto): array
    {
        $googleData = $this->socialAuthService->verifyGoogleToken($dto->googleIdToken);

        $user = DB::transaction(function () use ($googleData): User {
            // 1. Check if this Google account is already linked
            $socialAccount = DB::table('social_accounts')
                ->where('provider', 'google')
                ->where('provider_id', $googleData['provider_id'])
                ->first();

            if ($socialAccount) {
                return User::findOrFail($socialAccount->user_id);
            }

            // 2. Check if user exists by email (account merging)
            $user = User::where('email', $googleData['email'])->first();

            if (! $user) {
                // 3. New user — create account
                $user = User::create([
                    'uuid'   => Str::uuid()->toString(),
                    'email'  => $googleData['email'],
                    'role'   => 'regular',
                    'status' => 'active',
                ]);

                // Create wallet for new user
                $user->wallet()->create([
                    'available_balance' => 0,
                    'held_balance'      => 0,
                    'total_earned'      => 0,
                    'total_spent'       => 0,
                ]);

                Log::info('New user registered via Google', ['user_id' => $user->id]);
            } else {
                Log::info('Existing user linked Google account', ['user_id' => $user->id]);
            }

            // Link the Google social account
            DB::table('social_accounts')->insert([
                'user_id'     => $user->id,
                'provider'    => 'google',
                'provider_id' => $googleData['provider_id'],
                'created_at'  => now(),
            ]);

            return $user;
        });

        if ($user->is_banned || $user->status === 'suspended') {
            throw new UnauthorizedException('Your account has been suspended.');
        }

        $user->update(['last_active_at' => now()]);

        return $this->buildTokenResponse($user);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    /**
     * Invalidate the current JWT token.
     */
    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    // ─── Me ───────────────────────────────────────────────────────────────────

    /**
     * Return the currently authenticated user.
     */
    public function me(): User
    {
        /** @var User */
        return auth('api')->user();
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Resolve an identifier string into a [column, value] pair.
     *
     * @return array{string, string}
     */
    private function resolveIdentifier(string $identifier): array
    {
        if (str_contains($identifier, '@')) {
            return ['email', strtolower(trim($identifier))];
        }

        // Treat as phone — strip common formatting for consistency
        return ['phone', preg_replace('/\s+/', '', $identifier)];
    }

    /**
     * Issue a JWT and build the standard token response array.
     *
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    private function buildTokenResponse(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60, // TTL in seconds
            'user'         => $user,
        ];
    }
}
