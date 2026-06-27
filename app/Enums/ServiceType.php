<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceType: string
{
    case Message = 'message';
    case VoiceMessage = 'voice_message';
    case VideoCall = 'video_call';
    case LiveStream = 'live_stream';
    case MeetGreet = 'meet_greet';
    case GroupCall = 'group_call';
    case AiChat = 'ai_chat';

    public function isCallType(): bool
    {
        return match ($this) {
            self::VideoCall, self::LiveStream, self::GroupCall => true,
            default => false,
        };
    }

    public function isFutureFeature(): bool
    {
        return match ($this) {
            self::LiveStream, self::MeetGreet, self::GroupCall, self::AiChat => true,
            default => false,
        };
    }
}
