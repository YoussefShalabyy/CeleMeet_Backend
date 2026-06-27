<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for media storage provider implementations (e.g. Cloudinary).
 *
 * Business logic must ONLY depend on this interface.
 * Never import Cloudinary SDK classes outside of Infrastructure/MediaStorage/.
 */
interface MediaStorageInterface
{
    /**
     * Upload a file to the media storage provider.
     *
     * @param  string  $filePath  Absolute path to the local file.
     * @param  string  $collection  Logical collection name (e.g., 'avatar', 'post', 'story').
     * @param  array<string, mixed>  $options  Provider-specific upload options.
     * @return array{
     *     provider_id: string,
     *     url: string,
     *     mime_type: string|null,
     *     size: int|null,
     *     width: int|null,
     *     height: int|null,
     *     duration: int|null,
     *     metadata: array<string, mixed>
     * }
     */
    public function upload(string $filePath, string $collection, array $options = []): array;

    /**
     * Delete a file from the media storage provider.
     *
     * @param  string  $providerId  The provider's asset ID.
     * @return bool True if deleted successfully.
     */
    public function delete(string $providerId): bool;

    /**
     * Get the public URL for a stored asset.
     *
     * @param  string  $providerId  The provider's asset ID.
     * @param  array<string, mixed>  $transformations  Optional transformation parameters.
     */
    public function getUrl(string $providerId, array $transformations = []): string;
}
