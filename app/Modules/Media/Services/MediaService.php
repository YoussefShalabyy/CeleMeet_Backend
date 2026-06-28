<?php

declare(strict_types=1);

namespace App\Modules\Media\Services;

use App\Contracts\MediaStorageInterface;
use App\Models\MediaAsset;
use App\Modules\Media\DTOs\UploadMediaDTO;

final class MediaService
{
    public function __construct(
        private readonly MediaStorageInterface $mediaStorage
    ) {}

    public function upload(UploadMediaDTO $dto): MediaAsset
    {
        $filePath = $dto->file->getRealPath();
        
        $result = $this->mediaStorage->upload($filePath, $dto->collection);

        return MediaAsset::create([
            'owner_id'    => $dto->ownerId,
            'owner_type'  => $dto->ownerType,
            'collection'  => $dto->collection,
            'provider'    => 'cloudinary', // We assume Cloudinary or Fake based on the interface, but this labels the source
            'provider_id' => $result['provider_id'],
            'url'         => $result['url'],
            'mime_type'   => $result['mime_type'],
            'size'        => $result['size'],
            'width'       => $result['width'],
            'height'      => $result['height'],
            'duration'    => $result['duration'],
            'metadata'    => $result['metadata'],
        ]);
    }

    public function delete(MediaAsset $mediaAsset): bool
    {
        $deletedFromProvider = $this->mediaStorage->delete($mediaAsset->provider_id);

        if ($deletedFromProvider) {
            return $mediaAsset->delete();
        }

        return false;
    }
}
