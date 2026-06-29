<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\PaidMessage;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private function createCreator(): User
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create(['user_id' => $creator->id, 'display_name' => 'Test Creator']);
        return $creator;
    }

    public function test_user_can_get_chat_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Uses the FakeChatProvider which just returns 'fake-token-for-user-X'
        $response = $this->withToken($token)->postJson('/api/v1/chat/token');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'token', 'apiKey', 'user']);
    }

    public function test_user_can_send_paid_message(): void
    {
        $creator = $this->createCreator();
        
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 100]);
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/messages/send', [
            'receiver_id' => $creator->id,
            'content'     => 'Hello Creator!',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('paid_messages', [
            'sender_id'      => $user->id,
            'receiver_id'    => $creator->id,
            'content'        => 'Hello Creator!',
            'price_in_coins' => 10, // Assuming 10 coins is the default
        ]);

        // Wallet was deducted 10 coins
        $this->assertEquals(90, Wallet::where('user_id', $user->id)->value('available_balance'));
    }

    public function test_user_cannot_send_message_without_funds(): void
    {
        $creator = $this->createCreator();
        
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 5]); // Need 10
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/messages/send', [
            'receiver_id' => $creator->id,
            'content'     => 'Hello Creator!',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_refund_unanswered_eligible_message(): void
    {
        $creator = $this->createCreator();
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 0]);
        $token = auth('api')->login($user);

        // Manually create a paid message eligible for refund
        $message = PaidMessage::create([
            'sender_id'             => $user->id,
            'receiver_id'           => $creator->id,
            'message_type'          => 'text',
            'content'               => 'Test',
            'price_in_coins'        => 10,
            'status'                => 'sent',
            // Made it eligible in the past
            'refund_eligible_until' => now()->subHour(), 
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/messages/{$message->id}/refund");

        $response->assertStatus(200);

        // Wallet refunded
        $this->assertEquals(10, Wallet::where('user_id', $user->id)->value('available_balance'));

        // Refund recorded
        $this->assertDatabaseHas('message_refunds', [
            'paid_message_id' => $message->id,
            'user_id'         => $user->id,
            'coins_returned'  => 10,
        ]);
        
        // Message status updated
        $this->assertEquals('refunded', $message->fresh()->status);
    }

    public function test_user_cannot_refund_ineligible_message(): void
    {
        $creator = $this->createCreator();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $message = PaidMessage::create([
            'sender_id'             => $user->id,
            'receiver_id'           => $creator->id,
            'message_type'          => 'text',
            'content'               => 'Test',
            'price_in_coins'        => 10,
            'status'                => 'sent',
            // Still in the 24 hour window
            'refund_eligible_until' => now()->addHours(23), 
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/messages/{$message->id}/refund");

        $response->assertStatus(422); // Not eligible yet
    }
}
