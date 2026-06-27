<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for video/voice call provider implementations (e.g. Stream Video, Agora).
 *
 * Business logic must ONLY depend on this interface.
 * Never import provider SDK classes outside of Infrastructure/VideoCall/.
 */
interface VideoCallProviderInterface
{
    /**
     * Create a call session on the provider.
     *
     * @param  string  $callId  Unique call identifier (our internal ID as string).
     * @param  int  $callerId  The user initiating the call.
     * @param  int  $calleeId  The creator receiving the call.
     * @param  string  $type  'audio' or 'video'.
     * @return string The provider's session/call ID.
     */
    public function createCall(string $callId, int $callerId, int $calleeId, string $type): string;

    /**
     * End an active call on the provider.
     *
     * @param  string  $providerCallId  The provider's call ID.
     */
    public function endCall(string $providerCallId): void;

    /**
     * Generate a scoped token for the given user to join a call.
     *
     * @param  string  $callId  The provider's call ID.
     * @return string A short-lived access token.
     */
    public function generateCallToken(int $userId, string $callId): string;

    /**
     * Generate a user token for the video SDK (not call-specific).
     */
    public function generateUserToken(int $userId): string;
}
