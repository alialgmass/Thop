<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verification documents
    |--------------------------------------------------------------------------
    |
    | The private disk documents are written to, the accepted file types, and
    | the size ceiling. The size ceiling is an Implementation Assumption — the
    | source document does not name one — defaulted to 10 MB and admin-tunable.
    |
    */

    'disk' => env('VERIFICATION_DISK', 'verification'),

    'accepted_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],

    'accepted_mimetypes' => ['application/pdf', 'image/jpeg', 'image/png'],

    'max_file_size_kb' => (int) env('VERIFICATION_MAX_FILE_SIZE_KB', 10240),

    // TTL for the signed, time-limited download link handed to an owner or admin
    // (spec Section 12 / Section 15).
    'download_link_ttl_seconds' => (int) env('VERIFICATION_DOWNLOAD_TTL', 300),
];
