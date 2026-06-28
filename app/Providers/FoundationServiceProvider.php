<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ChatProviderInterface;
use App\Contracts\MediaStorageInterface;
use App\Contracts\NotificationProviderInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\VideoCallProviderInterface;
use App\Infrastructure\Chat\FakeChatProvider;
use App\Infrastructure\MediaStorage\FakeMediaStorage;
use App\Infrastructure\Notification\FakeNotificationProvider;
use App\Infrastructure\Payment\FakePaymentGateway;
use App\Infrastructure\VideoCall\FakeVideoCallProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registers all infrastructure provider bindings.
 *
 * This service provider maps Contracts (Interfaces) to their concrete Adapters.
 * Currently uses Fake adapters so the application boots without any external credentials.
 *
 * When a real adapter is ready, swap the binding here — nowhere else.
 * Business logic depends on the Interface, never on the concrete class.
 *
 * Swap order when going to production:
 *   FakeChatProvider          → StreamChatAdapter
 *   FakeVideoCallProvider     → StreamVideoAdapter
 *   FakeMediaStorage          → CloudinaryAdapter
 *   FakePaymentGateway        → PaymobAdapter
 *   FakeNotificationProvider  → ExpoNotificationAdapter
 */
class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Register all contract → adapter bindings.
     */
    public function register(): void
    {
        $this->bindChatProvider();
        $this->bindVideoCallProvider();
        $this->bindMediaStorage();
        $this->bindPaymentGateway();
        $this->bindNotificationProvider();
    }

    public function boot(): void
    {
        //
    }

    private function bindChatProvider(): void
    {
        $this->app->bind(
            ChatProviderInterface::class,
            FakeChatProvider::class,
        );
    }

    private function bindVideoCallProvider(): void
    {
        $this->app->bind(
            VideoCallProviderInterface::class,
            FakeVideoCallProvider::class,
        );
    }

    private function bindMediaStorage(): void
    {
        $this->app->bind(
            MediaStorageInterface::class,
            \App\Infrastructure\MediaStorage\CloudinaryAdapter::class,
        );
    }

    private function bindPaymentGateway(): void
    {
        $this->app->bind(
            PaymentGatewayInterface::class,
            FakePaymentGateway::class,
        );
    }

    private function bindNotificationProvider(): void
    {
        $this->app->bind(
            NotificationProviderInterface::class,
            FakeNotificationProvider::class,
        );
    }
}
