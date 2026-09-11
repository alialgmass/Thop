<?php

return [
    'report' => [
        'label' => 'بلاغ',
        'plural' => 'البلاغات',

        'columns' => [
            'type' => 'النوع',
            'reason' => 'السبب',
            'reporter' => 'المُبلِّغ',
            'status' => 'الحالة',
            'reported_at' => 'تاريخ البلاغ',
        ],

        'sections' => [
            'report' => 'البلاغ',
            'parties' => 'الأطراف',
            'resolution' => 'الحسم',
        ],

        'fields' => [
            'buyer' => 'المشتري',
            'seller' => 'البائع',
            'resolved_by' => 'حسمه',
            'resolved_at' => 'تاريخ الحسم',
            'resolution_note' => 'ملاحظة الحسم',
        ],

        'status' => [
            'resolved' => 'محسوم',
            'dismissed' => 'مرفوض',
        ],

        'actions' => [
            'resolve' => 'حسم',
            'resolved' => 'تم حسم البلاغ.',
        ],
    ],
];
