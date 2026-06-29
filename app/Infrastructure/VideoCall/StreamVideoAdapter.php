<?php

declare(strict_types=1);

namespace App\Infrastructure\VideoCall;

use App\Contracts\VideoCallProviderInterface;
use GetStream\StreamChat\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StreamVideoAdapter implements VideoCallProviderInterface
{
    private Client $client;

    public function __construct(
        private readonly string $apiKey, 
        private readonly string $apiSecret
    ) {
        $this->client = new Client($this->apiKey, $this->apiSecret, timeout: 10);
    }

    public function createCall(string $callId, int $callerId, int $calleeId, string $type): string
    {
        // Stream Video does not have an official PHP SDK yet. 
        // We can just return the generated call ID since Stream allows clients to join the call 
        // with just the call ID and token. The client handles the actual WebSocket connection.
        return $callId;
    }

    public function endCall(string $providerCallId): void
    {
        // To end a call on Stream Video via REST API, we would hit:
        // POST https://video.stream-io-api.com/api/v2/video/call/default/{id}/end
        
        try {
            Http::withHeaders([
                'stream-auth-type' => 'jwt',
                // For Server-to-Server, Stream often expects the server token
            ])
            ->post("https://video.stream-io-api.com/api/v2/video/call/default/{$providerCallId}/end", [
                'api_key' => $this->apiKey,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to end Stream call {$providerCallId}: " . $e->getMessage());
        }
    }

    public function generateCallToken(int $userId, string $callId): string
    {
        // Stream Video uses the same token logic as Chat.
        return $this->client->createToken((string) $userId);
    }

    public function generateUserToken(int $userId): string
    {
        // Same here
        return $this->client->createToken((string) $userId);
    }
}
