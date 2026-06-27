<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Cloud Name
    |--------------------------------------------------------------------------
    */
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('CLOUDINARY_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary API Secret
    |--------------------------------------------------------------------------
    */
    'api_secret' => env('CLOUDINARY_API_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Upload Presets
    |--------------------------------------------------------------------------
    | Named upload presets configured in the Cloudinary dashboard.
    | Presets define transformation pipelines, quality, and folder structure.
    */
    'presets' => [
        'avatar' => env('CLOUDINARY_PRESET_AVATAR', 'celebmeet_avatar'),
        'cover' => env('CLOUDINARY_PRESET_COVER', 'celebmeet_cover'),
        'post' => env('CLOUDINARY_PRESET_POST', 'celebmeet_post'),
        'story' => env('CLOUDINARY_PRESET_STORY', 'celebmeet_story'),
        'message' => env('CLOUDINARY_PRESET_MESSAGE', 'celebmeet_message'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Folder Structure
    |--------------------------------------------------------------------------
    | Root folder in Cloudinary where all assets are stored.
    */
    'root_folder' => env('CLOUDINARY_ROOT_FOLDER', 'celebmeet'),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    */
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'allowed_video_types' => ['video/mp4', 'video/quicktime', 'video/webm'],
    'allowed_audio_types' => ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg'],

    /*
    |--------------------------------------------------------------------------
    | Max Upload Size (bytes)
    |--------------------------------------------------------------------------
    */
    'max_image_size' => (int) env('CLOUDINARY_MAX_IMAGE_SIZE', 10 * 1024 * 1024),  // 10 MB
    'max_video_size' => (int) env('CLOUDINARY_MAX_VIDEO_SIZE', 100 * 1024 * 1024), // 100 MB
    'max_audio_size' => (int) env('CLOUDINARY_MAX_AUDIO_SIZE', 20 * 1024 * 1024),  // 20 MB

    /*
    |--------------------------------------------------------------------------
    | Signed Uploads
    |--------------------------------------------------------------------------
    | Whether to use signed uploads (more secure, server-side signing required).
    */
    'signed_uploads' => (bool) env('CLOUDINARY_SIGNED_UPLOADS', true),

];
