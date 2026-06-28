<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_media(): void
    {
        $this->postJson('/api/v1/media/upload')->assertStatus(401);
    }

    public function test_authenticated_user_can_upload_media(): void
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withToken($token)
            ->postJson('/api/v1/media/upload', [
                'file'       => $file,
                'collection' => 'avatar',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully.',
            ])
            ->assertJsonStructure(['data' => ['id', 'url', 'mime_type', 'width', 'height', 'duration']]);

        $this->assertDatabaseHas('media_assets', [
            'owner_id'   => $user->id,
            'owner_type' => User::class,
            'collection' => 'avatar',
            'provider'   => 'cloudinary',
        ]);
    }

    public function test_upload_fails_with_invalid_collection(): void
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $this->withToken($token)
            ->postJson('/api/v1/media/upload', [
                'file'       => $file,
                'collection' => 'invalid_collection',
            ])->assertStatus(422);
    }

    public function test_guest_cannot_delete_media(): void
    {
        $this->deleteJson('/api/v1/media/1')->assertStatus(401);
    }

    public function test_user_can_delete_own_media(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $media = MediaAsset::create([
            'owner_id'    => $user->id,
            'owner_type'  => User::class,
            'collection'  => 'post',
            'provider'    => 'cloudinary',
            'provider_id' => 'fake_provider_id',
            'url'         => 'https://example.com/image.png',
            'mime_type'   => 'image/png',
            'size'        => 1024,
            'width'       => 800,
            'height'      => 600,
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/media/{$media->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Media deleted successfully.']);

        $this->assertDatabaseMissing('media_assets', ['id' => $media->id]);
    }

    public function test_user_cannot_delete_others_media(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = auth('api')->login($otherUser);

        $media = MediaAsset::create([
            'owner_id'    => $owner->id,
            'owner_type'  => User::class,
            'collection'  => 'post',
            'provider'    => 'cloudinary',
            'provider_id' => 'fake_provider_id',
            'url'         => 'https://example.com/image.png',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/media/{$media->id}")
            ->assertStatus(403);
            
        $this->assertDatabaseHas('media_assets', ['id' => $media->id]);
    }
}
