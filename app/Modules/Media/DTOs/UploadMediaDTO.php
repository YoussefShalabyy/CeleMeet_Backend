<?php

declare(strict_types=1);

namespace App\Modules\Media\DTOs;

use App\Support\DTOs\BaseDTO;
use Illuminate\Http\UploadedFile;

final class UploadMediaDTO extends BaseDTO
{
    public function __construct(
        public readonly UploadedFile $file,
        public readonly string $collection,
        public readonly int $ownerId,
        public readonly string $ownerType,
    ) {}
}
