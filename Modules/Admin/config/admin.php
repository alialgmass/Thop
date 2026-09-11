<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Homepage banners (Phase 9 · T6, issue #35)
    |--------------------------------------------------------------------------
    |
    | The banner image lives on the public disk (local in dev, S3+CDN in
    | production) — same convention as Catalog product media (§12).
    |
    */
    'banners' => [
        'disk' => env('BANNER_MEDIA_DISK', 'public'),
        'max_file_size_kb' => (int) env('BANNER_MEDIA_MAX_KB', 5120),
        'accepted_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'accepted_mimetypes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
];
