<?php

return [
    'audit_log' => [
        'label' => 'سجل تدقيق',
        'plural' => 'سجل التدقيق',
    ],

    'columns' => [
        'when' => 'التوقيت',
        'actor' => 'المنفّذ',
        'action' => 'الإجراء',
        'entity' => 'العنصر',
        'entity_id' => 'معرّف العنصر',
    ],

    'filters' => [
        'action' => 'الإجراء',
        'actor' => 'المنفّذ',
        'entity' => 'العنصر',
    ],

    'banner' => [
        'label' => 'بانر',
        'plural' => 'البانرات',
        'fields' => [
            'image' => 'الصورة',
            'link_url' => 'رابط',
            'position' => 'الترتيب',
            'starts_at' => 'يبدأ في',
            'starts_at_hint' => 'اتركه فارغًا للبدء فورًا.',
            'ends_at' => 'ينتهي في',
            'ends_at_hint' => 'اتركه فارغًا بدون تاريخ انتهاء.',
            'immediately' => 'فورًا',
            'no_end' => 'بدون تاريخ انتهاء',
            'is_active' => 'مفعّل',
        ],
        'actions' => [
            'remove' => 'إزالة',
            'removed' => 'تمت إزالة البانر.',
        ],
    ],
];
