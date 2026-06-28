<?php

declare(strict_types=1);

namespace App\Infrastructure\MediaStorage;

use App\Contracts\MediaStorageInterface;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryAdapter implements MediaStorageInterface
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $url = config('services.cloudinary.url');
        $this->cloudinary = new Cloudinary(Configuration::instance($url));
    }

    public function upload(string $filePath, string $collection, array $options = []): array
    {
        $defaultOptions = [
            'folder' => 'celemeet/' . $collection,
            'resource_type' => 'auto',
        ];

        $uploadOptions = array_merge($defaultOptions, $options);
        $result = $this->cloudinary->uploadApi()->upload($filePath, $uploadOptions);

        return [
            'provider_id' => $result['public_id'],
            'url'         => $result['secure_url'],
            'mime_type'   => $result['format'] ? $result['resource_type'] . '/' . $result['format'] : null,
            'size'        => $result['bytes'] ?? null,
            'width'       => $result['width'] ?? null,
            'height'      => $result['height'] ?? null,
            'duration'    => isset($result['duration']) ? (int) $result['duration'] : null,
            'metadata'    => $result,
        ];
    }

    public function delete(string $providerId): bool
    {
        $result = $this->cloudinary->uploadApi()->destroy($providerId);
        
        return isset($result['result']) && $result['result'] === 'ok';
    }

    public function getUrl(string $providerId, array $transformations = []): string
    {
        // For advanced on-the-fly transformations, we could use Cloudinary SDK's url generation.
        // But typically the database stores the direct secure_url on upload.
        // Providing basic implementation.
        return $this->cloudinary->image($providerId)->toUrl();
    }
}
