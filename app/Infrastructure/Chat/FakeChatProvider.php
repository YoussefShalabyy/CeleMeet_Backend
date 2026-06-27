<?php

declare(strict_types=1);

namespace App\Infrastructure\Chat;

use App\Contracts\ChatProviderInterface;

/**
 * Fake implementation of ChatProviderInterface for development and testing.
 *
 * This adapter returns predictable, safe values without making any HTTP calls.
 * It should be used in all non-integration tests and in local dev without Stream credentials.
 */
class FakeChatProvider implements ChatProviderInterface
{
    public function getOrCreateChannel(string $channelId, int $creatorId, int $senderId): string
    {
        return 'fake_channel_'.$channelId;
    }

    public function sendMessage(string $channelId, int $senderId, string $content, array $metadata = []): string
    {
        return 'fake_msg_'.uniqid();
    }

    public function generateUserToken(int $userId): string
    {
        return 'fake_chat_token_'.$userId.'_'.time();
    }

    public function upsertUser(int $userId, string $name, ?string $avatarUrl = null): void
    {
        // No-op in fake implementation
    }
}
