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
];
