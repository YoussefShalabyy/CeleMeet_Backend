<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Recharge = 'recharge';
    case Message = 'message';
    case VoiceMessage = 'voice_message';
    case VoiceCall = 'voice_call';
    case VideoCall = 'video_call';
    case Subscription = 'subscription';
    case Gift = 'gift';
    case Refund = 'refund';
    case Withdrawal = 'withdrawal';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Recharge => 'Coin Recharge',
            self::Message => 'Paid Message',
            self::VoiceMessage => 'Voice Message',
            self::VideoCall => 'Video Call',
            self::Subscription => 'Subscription',
            self::Gift => 'Gift',
            self::Refund => 'Refund',
            self::Withdrawal => 'Withdrawal',
            self::AdminAdjustment => 'Admin Adjustment',
        };
    }

    /** Returns true if this transaction type adds coins to the wallet. */
    public function isCredit(): bool
    {
        return match ($this) {
            self::Recharge, self::Refund, self::AdminAdjustment => true,
            default => false,
        };
    }

    /** Returns true if this transaction type removes coins from the wallet. */
    public function isDebit(): bool
    {
        return ! $this->isCredit();
    }
}
