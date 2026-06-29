<?php

declare(strict_types=1);

namespace App\Modules\Chat\DTOs;

use App\Support\DTOs\BaseDTO;

final class SendMessageDTO extends BaseDTO
{
    public function __construct(
        public readonly int $senderId,
        public readonly int $receiverId,
        public readonly string $content,
        public readonly string $messageType = 'text',
        public readonly ?int $mediaAssetId = null,
    ) {}
}
