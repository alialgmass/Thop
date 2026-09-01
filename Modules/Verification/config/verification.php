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

    'max_file_size_kb' => (int) env('VERIFICATION_MAX_FILE_SIZE_KB', 10240),

    // TTL for the signed download link handed to an owner or admin.
    'download_link_ttl_seconds' => 300,
];
