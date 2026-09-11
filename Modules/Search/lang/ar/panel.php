<?php

return [
    'featured' => [
        'label' => 'عنصر مميز',
        'plural' => 'العناصر المميزة',
    ],

    'fields' => [
        'type' => 'النوع',
        'featurable_id' => 'معرّف العنصر',
        'featurable_id_hint' => 'معرّف المنتج أو المورد المراد تمييزه.',
        'slot' => 'الموضع',
        'starts_at' => 'يبدأ في',
        'starts_at_hint' => 'اتركه فارغًا للبدء فورًا.',
        'ends_at' => 'ينتهي في',
        'ends_at_hint' => 'اتركه فارغًا بدون تاريخ انتهاء.',
        'immediately' => 'فورًا',
        'no_end' => 'بدون تاريخ انتهاء',
        'active_now' => 'مفعّل الآن',
        'created_by' => 'أنشأه',
    ],

    'types' => [
        'product' => 'منتج',
        'supplier' => 'مورد',
    ],

    'actions' => [
        'remove' => 'إزالة',
        'removed' => 'تمت إزالة العنصر المميز.',
    ],

    'messages' => [
        'featurable_not_found' => 'هذا المنتج أو المورد غير موجود.',
    ],
];
