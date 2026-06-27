<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for push notification provider implementations (e.g. Expo, Firebase FCM).
 *
 * Business logic must ONLY depend on this interface.
 * Never import provider SDK classes outside of Infrastructure/Notification/.
 *
 * IMPORTANT: Notification failures must NEVER affect business operations.
 * Implementations must catch and log all provider errors silently.
 */
interface NotificationProviderInterface
{
    /**
     * Send a push notification to a single device token.
     *
     * @param  string  $deviceToken  The recipient's push token.
     * @param  string  $title  Notification title.
     * @param  string  $body  Notification body.
     * @param  array<string, mixed>  $data  Optional payload data for the mobile app.
     * @return bool True if sent successfully, false on failure.
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool;

    /**
     * Send a push notification to multiple device tokens.
     *
     * @param  string[]  $deviceTokens  List of push tokens.
     * @param  array<string, mixed>  $data
     * @return int Number of tokens successfully notified.
     */
    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = []): int;
}
