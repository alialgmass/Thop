<?php

return [
    'featured' => [
        'label' => 'Featured placement',
        'plural' => 'Featured placements',
    ],

    'fields' => [
        'type' => 'Type',
        'featurable_id' => 'Item ID',
        'featurable_id_hint' => 'The product or supplier ID to feature.',
        'slot' => 'Slot',
        'starts_at' => 'Starts at',
        'starts_at_hint' => 'Leave empty to start immediately.',
        'ends_at' => 'Ends at',
        'ends_at_hint' => 'Leave empty for no end date.',
        'immediately' => 'Immediately',
        'no_end' => 'No end date',
        'active_now' => 'Active now',
        'created_by' => 'Created by',
    ],

    'types' => [
        'product' => 'Product',
        'supplier' => 'Supplier',
    ],

    'actions' => [
        'remove' => 'Remove',
        'removed' => 'Placement removed.',
    ],

    'messages' => [
        'featurable_not_found' => 'That product or supplier does not exist.',
    ],
];
