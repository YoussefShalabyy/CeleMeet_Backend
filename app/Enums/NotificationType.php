<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case NewPost = 'new_post';
    case NewStory = 'new_story';
    case NewMessage = 'new_message';
    case MessageReply = 'message_reply';
    case SubscriptionExpiring = 'subscription_expiring';
    case SubscriptionExpired = 'subscription_expired';
    case CallReminder = 'call_reminder';
    case NewPremiumContent = 'new_premium_content';
    case NewFollower = 'new_follower';
    case WithdrawalApproved = 'withdrawal_approved';
    case WithdrawalRejected = 'withdrawal_rejected';

    public function label(): string
    {
        return match ($this) {
            self::NewPost => 'New Post',
            self::NewStory => 'New Story',
            self::NewMessage => 'New Message',
            self::MessageReply => 'Message Reply',
            self::SubscriptionExpiring => 'Subscription Expiring Soon',
            self::SubscriptionExpired => 'Subscription Expired',
            self::CallReminder => 'Call Reminder',
            self::NewPremiumContent => 'New Premium Content',
            self::NewFollower => 'New Follower',
            self::WithdrawalApproved => 'Withdrawal Approved',
            self::WithdrawalRejected => 'Withdrawal Rejected',
        };
    }
}
