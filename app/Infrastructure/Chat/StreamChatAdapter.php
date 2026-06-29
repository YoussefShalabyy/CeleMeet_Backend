<?php

declare(strict_types=1);

namespace App\Infrastructure\Chat;

use App\Contracts\ChatProviderInterface;
use GetStream\StreamChat\Client;

class StreamChatAdapter implements ChatProviderInterface
{
    private Client $client;

    public function __construct(string $apiKey, string $apiSecret)
    {
        // Add timeout to prevent hanging the PHP process
        $this->client = new Client($apiKey, $apiSecret, timeout: 10);
    }

    public function getOrCreateChannel(string $channelId, int $creatorId, int $senderId): string
    {
        $channel = $this->client->Channel('messaging', $channelId);
        
        $channel->create((string) $creatorId, [
            (string) $creatorId,
            (string) $senderId,
        ]);

        return $channelId;
    }

    public function sendMessage(string $channelId, int $senderId, string $content, array $metadata = []): string
    {
        $channel = $this->client->Channel('messaging', $channelId);
        
        $messageData = [
            'text' => $content,
        ];
        
        if (!empty($metadata)) {
            $messageData = array_merge($messageData, $metadata);
        }

        $response = $channel->sendMessage($messageData, (string) $senderId);
        
        return $response['message']['id'];
    }

    public function generateUserToken(int $userId): string
    {
        return $this->client->createToken((string) $userId);
    }

    public function upsertUser(int $userId, string $name, ?string $avatarUrl = null): void
    {
        $userData = [
            'id' => (string) $userId,
            'name' => $name,
        ];

        if ($avatarUrl) {
            $userData['image'] = $avatarUrl;
        }

        $this->client->upsertUser($userData);
    }
}
