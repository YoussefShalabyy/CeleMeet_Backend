<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Contracts\NotificationProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Fake implementation of NotificationProviderInterface for development and testing.
 *
 * Logs the notification instead of sending it.
 * Always returns success to avoid interfering with business logic.
 */
class FakeNotificationProvider implements NotificationProviderInterface
{
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        Log::debug('FakeNotificationProvider: sendToDevice', [
            'device_token' => substr($deviceToken, 0, 10).'...',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return true;
    }

    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = []): int
    {
        Log::debug('FakeNotificationProvider: sendToDevices', [
            'device_count' => count($deviceTokens),
            'title' => $title,
            'body' => $body,
        ]);

        return count($deviceTokens);
    }
}
