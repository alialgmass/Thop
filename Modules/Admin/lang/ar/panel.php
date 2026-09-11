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

    'dashboard' => [
        'active_sellers' => 'البائعون النشِطون',
        'active_products' => 'المنتجات النشِطة',
        'active_buyers' => 'المشترون النشِطون',
        'inquiries' => 'الاستفسارات',
        'top_zero_result_term' => 'أكثر كلمة بحث بلا نتائج',
        'zero_result_hint' => 'كلمة بحث مرجعتش نتائج — طلب غير مُلبّى.',
        'last_days' => 'آخر :days يوم',
        'none' => 'لا يوجد',
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
