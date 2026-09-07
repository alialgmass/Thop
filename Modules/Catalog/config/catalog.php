<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin review gating (BR-SEL-02)
    |--------------------------------------------------------------------------
    |
    | New products enter pending_review before becoming public when review_create
    | is true. A material edit sends an already-published product back into
    | pending_review when review_edit is true (default OFF so minor corrections
    | are not gated). These are seed defaults; the DB-backed override table
    | (catalog_config, read via CatalogConfig) lets an admin flip them without
    | a deploy. The admin management UI arrives in Phase 9.
    |
    */
    'review_create' => env('CATALOG_REVIEW_CREATE', true),
    'review_edit' => env('CATALOG_REVIEW_EDIT', false),

    /*
    |--------------------------------------------------------------------------
    | Product media (US-SEL-03, §12)
    |--------------------------------------------------------------------------
    |
    | Images live on the public disk (local in dev, S3+CDN in production).
    | A product needs at least one image before it may go public; drafts and
    | pending-review products may have none.
    |
    */
    'media' => [
        'disk' => env('PRODUCT_MEDIA_DISK', 'public'),
        'max_file_size_kb' => (int) env('PRODUCT_MEDIA_MAX_KB', 5120),
        'max_per_product' => (int) env('PRODUCT_MEDIA_MAX_PER_PRODUCT', 10),
        'accepted_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'accepted_mimetypes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk import (Phase 3.3, US-SEL-09/US-SEL-10)
    |--------------------------------------------------------------------------
    |
    | Uploaded CSVs are stashed on a private disk for the queued job to read.
    | XLSX support is deferred until a reader library is approved on the issue.
    |
    */
    'import' => [
        'disk' => env('PRODUCT_IMPORT_DISK', 'local'),
    ],
];
