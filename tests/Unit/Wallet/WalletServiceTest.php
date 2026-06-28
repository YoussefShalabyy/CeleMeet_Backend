<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Wallet\DTOs\CreditWalletDTO;
use App\Modules\Wallet\DTOs\DeductWalletDTO;
use App\Modules\Wallet\DTOs\HoldWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
    }

    public function test_credit_adds_funds_and_creates_transaction(): void
    {
        $user = User::factory()->create();
        
        $this->service->credit(new CreditWalletDTO(
            userId: $user->id,
            amount: 100,
            transactionType: TransactionType::Recharge
        ));

        $wallet = $this->service->getBalance($user->id);
        $this->assertEquals(100, $wallet->available_balance);
        $this->assertEquals(0, $wallet->total_earned); // Recharge doesn't count to earnings

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'amount' => 100,
            'transaction_type' => TransactionType::Recharge->value,
            'status' => TransactionStatus::Completed->value,
        ]);
    }

    public function test_credit_adds_to_earnings_for_income_types(): void
    {
        $user = User::factory()->create();
        
        $this->service->credit(new CreditWalletDTO(
            userId: $user->id,
            amount: 50,
            transactionType: TransactionType::Gift
        ));

        $wallet = $this->service->getBalance($user->id);
        $this->assertEquals(50, $wallet->available_balance);
        $this->assertEquals(50, $wallet->total_earned); // Gift counts as earnings
    }

    public function test_deduct_fails_when_insufficient_funds(): void
    {
        $user = User::factory()->create();
        
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Insufficient balance.');

        $this->service->deduct(new DeductWalletDTO(
            userId: $user->id,
            amount: 50,
            transactionType: TransactionType::Message
        ));
    }

    public function test_deduct_subtracts_funds_and_adds_to_spent(): void
    {
        $user = User::factory()->create();
        
        // Seed some initial balance
        $wallet = $user->wallet;
        $wallet->update(['available_balance' => 100]);
        
        $this->service->deduct(new DeductWalletDTO(
            userId: $user->id,
            amount: 30,
            transactionType: TransactionType::Message
        ));

        $wallet->refresh();
        $this->assertEquals(70, $wallet->available_balance);
        $this->assertEquals(30, $wallet->total_spent);
        
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 30,
            'transaction_type' => TransactionType::Message->value,
        ]);
    }

    public function test_hold_moves_funds_correctly(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet;
        $wallet->update(['available_balance' => 100]);

        $this->service->hold(new HoldWalletDTO(
            userId: $user->id,
            amount: 40
        ));

        $wallet->refresh();
        $this->assertEquals(60, $wallet->available_balance);
        $this->assertEquals(40, $wallet->held_balance);
    }

    public function test_release_hold_returns_funds_to_available(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet;
        $wallet->update(['available_balance' => 60, 'held_balance' => 40]);

        $this->service->releaseHold(new HoldWalletDTO(
            userId: $user->id,
            amount: 40
        ), finalizeAsDeduction: false);

        $wallet->refresh();
        $this->assertEquals(100, $wallet->available_balance);
        $this->assertEquals(0, $wallet->held_balance);
    }

    public function test_release_hold_as_deduction_burns_funds_and_creates_transaction(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet;
        $wallet->update(['available_balance' => 60, 'held_balance' => 40]);

        $this->service->releaseHold(new HoldWalletDTO(
            userId: $user->id,
            amount: 40
        ), finalizeAsDeduction: true, type: TransactionType::VideoCall);

        $wallet->refresh();
        $this->assertEquals(60, $wallet->available_balance);
        $this->assertEquals(0, $wallet->held_balance);
        $this->assertEquals(40, $wallet->total_spent);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 40,
            'transaction_type' => TransactionType::VideoCall->value,
        ]);
    }
}
