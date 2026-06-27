<?php

declare(strict_types=1);

namespace App\Infrastructure\VideoCall;

use App\Contracts\VideoCallProviderInterface;

/**
 * Fake implementation of VideoCallProviderInterface for development and testing.
 *
 * Returns predictable values without any HTTP calls or Stream Video credentials.
 */
class FakeVideoCallProvider implements VideoCallProviderInterface
{
    public function createCall(string $callId, int $callerId, int $calleeId, string $type): string
    {
        return 'fake_call_'.$callId;
    }

    public function endCall(string $providerCallId): void
    {
        // No-op in fake implementation
    }

    public function generateCallToken(int $userId, string $callId): string
    {
        return 'fake_call_token_'.$userId.'_'.$callId.'_'.time();
    }

    public function generateUserToken(int $userId): string
    {
        return 'fake_video_token_'.$userId.'_'.time();
    }
}
