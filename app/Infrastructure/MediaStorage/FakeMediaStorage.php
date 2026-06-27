<?php

declare(strict_types=1);

namespace App\Infrastructure\MediaStorage;

use App\Contracts\MediaStorageInterface;

/**
 * Fake implementation of MediaStorageInterface for development and testing.
 *
 * Returns predictable, safe values without uploading anything to Cloudinary.
 * The returned URL is a placeholder that can be used for UI development.
 */
class FakeMediaStorage implements MediaStorageInterface
{
    public function upload(string $filePath, string $collection, array $options = []): array
    {
        $fakeId = 'fake_'.$collection.'_'.uniqid();

        return [
            'provider_id' => $fakeId,
            'url' => 'https://via.placeholder.com/800x600.png?text='.$collection,
            'mime_type' => 'image/png',
            'size' => 1024,
            'width' => 800,
            'height' => 600,
            'duration' => null,
            'metadata' => ['fake' => true, 'collection' => $collection],
        ];
    }

    public function delete(string $providerId): bool
    {
        return true;
    }

    public function getUrl(string $providerId, array $transformations = []): string
    {
        return 'https://via.placeholder.com/800x600.png?id='.$providerId;
    }
}
