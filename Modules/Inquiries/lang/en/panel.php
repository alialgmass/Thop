<?php

return [
    'report' => [
        'label' => 'Report',
        'plural' => 'Reports',

        'columns' => [
            'type' => 'Type',
            'reason' => 'Reason',
            'reporter' => 'Reported by',
            'status' => 'Status',
            'reported_at' => 'Reported at',
        ],

        'sections' => [
            'report' => 'Report',
            'parties' => 'Parties',
            'resolution' => 'Resolution',
        ],

        'fields' => [
            'buyer' => 'Buyer',
            'seller' => 'Seller',
            'resolved_by' => 'Resolved by',
            'resolved_at' => 'Resolved at',
            'resolution_note' => 'Resolution note',
        ],

        'status' => [
            'resolved' => 'Resolved',
            'dismissed' => 'Dismissed',
        ],

        'actions' => [
            'resolve' => 'Resolve',
            'resolved' => 'Report resolved.',
        ],
    ],
];
