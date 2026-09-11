<?php

return [
    'label' => 'مراجعة منتج',
    'plural' => 'مراجعة المنتجات',

    'columns' => [
        'name' => 'الاسم',
        'seller' => 'البائع',
        'images' => 'الصور',
        'status' => 'الحالة',
        'submitted_at' => 'تاريخ الإرسال',
    ],

    'sections' => [
        'product' => 'المنتج',
        'images' => 'الصور',
    ],

    'fields' => [
        'name_ar' => 'الاسم (عربي)',
        'name_en' => 'الاسم (إنجليزي)',
        'seller_phone' => 'هاتف البائع',
        'rejection_reason' => 'سبب الرفض / طلب التعديل',
        'description' => 'الوصف',
        'reason' => 'السبب',
    ],

    'actions' => [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'request_edits' => 'طلب تعديل',
        'hide' => 'إخفاء',
        'approved' => 'تمت الموافقة على المنتج.',
        'rejected' => 'تم رفض المنتج.',
        'edits_requested' => 'تم طلب التعديل، وعاد المنتج إلى المسودة.',
        'hidden' => 'تم إخفاء المنتج.',
    ],
];
