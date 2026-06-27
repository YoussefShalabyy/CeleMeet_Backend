<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for chat provider implementations (e.g. Stream Chat).
 *
 * Business logic must ONLY depend on this interface.
 * Never import Stream SDK classes outside of Infrastructure/Chat/.
 */
interface ChatProviderInterface
{
    /**
     * Create or retrieve a channel for a conversation between two users.
     *
     * @param  string  $channelId  Unique channel identifier.
     * @param  int  $creatorId  The creator (receiver) user ID.
     * @param  int  $senderId  The sender user ID.
     * @return string The provider's channel ID.
     */
    public function getOrCreateChannel(string $channelId, int $creatorId, int $senderId): string;

    /**
     * Send a message to an existing channel.
     *
     * @param  string  $channelId  The provider's channel ID.
     * @param  int  $senderId  The sender's user ID.
     * @param  string  $content  The message content.
     * @param  array<string, mixed>  $metadata  Optional message metadata.
     * @return string The provider's message ID.
     */
    public function sendMessage(string $channelId, int $senderId, string $content, array $metadata = []): string;

    /**
     * Generate a scoped token for the given user to access the chat SDK.
     *
     * @param  int  $userId  The user to generate a token for.
     * @return string A short-lived provider token.
     */
    public function generateUserToken(int $userId): string;

    /**
     * Upsert a user on the provider side.
     */
    public function upsertUser(int $userId, string $name, ?string $avatarUrl = null): void;
}
