<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SocialAuthService
{
    private const GOOGLE_TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    /**
     * Verify a Google id_token and return the user's Google profile data.
     *
     * @return array{email: string, provider_id: string, name: string}
     *
     * @throws BusinessException if the token is invalid or verification fails
     */
    public function verifyGoogleToken(string $idToken): array
    {
        $response = Http::get(self::GOOGLE_TOKENINFO_URL, [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            Log::warning('Google token verification failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new BusinessException('Invalid Google token. Please try signing in again.');
        }

        $data = $response->json();

        // Google returns error field when token is invalid
        if (isset($data['error'])) {
            throw new BusinessException('Invalid Google token: ' . ($data['error_description'] ?? 'unknown error'));
        }

        // Validate the token's audience matches our app
        $expectedClientId = config('services.google.client_id');

        if ($expectedClientId && ! in_array($expectedClientId, [$data['aud'] ?? '', $data['azp'] ?? ''], true)) {
            Log::error('Google token audience mismatch', [
                'expected' => $expectedClientId,
                'received' => $data['aud'] ?? 'none',
            ]);

            throw new BusinessException('Google token audience mismatch.');
        }

        if (empty($data['sub']) || empty($data['email'])) {
            throw new BusinessException('Google token missing required fields.');
        }

        return [
            'provider_id' => $data['sub'],
            'email'       => $data['email'],
            'name'        => $data['name'] ?? '',
        ];
    }
}
