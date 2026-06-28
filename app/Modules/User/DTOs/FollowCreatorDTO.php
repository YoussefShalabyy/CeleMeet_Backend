<?php

declare(strict_types=1);

namespace App\Modules\User\DTOs;

use App\Support\DTOs\BaseDTO;

final class FollowCreatorDTO extends BaseDTO
{
    public function __construct(
        public readonly int $followerId,
        public readonly int $creatorId,
    ) {}
}
