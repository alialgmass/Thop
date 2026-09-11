<?php

return [
    'label' => 'Product review',
    'plural' => 'Product reviews',

    'columns' => [
        'name' => 'Name',
        'seller' => 'Seller',
        'images' => 'Images',
        'status' => 'Status',
        'submitted_at' => 'Submitted at',
    ],

    'sections' => [
        'product' => 'Product',
        'images' => 'Images',
    ],

    'fields' => [
        'name_ar' => 'Name (Arabic)',
        'name_en' => 'Name (English)',
        'seller_phone' => 'Seller phone',
        'rejection_reason' => 'Rejection / edits reason',
        'description' => 'Description',
        'reason' => 'Reason',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'request_edits' => 'Request edits',
        'hide' => 'Hide',
        'approved' => 'Product approved.',
        'rejected' => 'Product rejected.',
        'edits_requested' => 'Edits requested; product returned to draft.',
        'hidden' => 'Product hidden.',
    ],
];
