<?php

return [
    'brand' => 'إدارة ثوب',

    'nav' => [
        'moderation' => 'المراجعة',
        'billing' => 'الاشتراكات',
        'access_control' => 'الصلاحيات',
        'system' => 'النظام',
    ],

    'common' => [
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'guard_name' => 'الحارس',
    ],

    'user' => [
        'label' => 'مستخدم',
        'plural' => 'المستخدمون',
        'fields' => [
            'phone' => 'رقم الهاتف',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'account_type' => 'نوع الحساب',
            'language' => 'اللغة',
            'status' => 'الحالة',
        ],
        'actions' => [
            'suspend' => 'إيقاف',
            'suspend_confirm' => 'أفهم أن هذا يوقف وصول هذا الحساب فورًا.',
            'suspended' => 'تم إيقاف الحساب.',
            'reactivate' => 'إعادة تفعيل',
            'reactivated' => 'تمت إعادة تفعيل الحساب.',
        ],
    ],

    'role' => [
        'label' => 'دور',
        'plural' => 'الأدوار',
        'fields' => [
            'name' => 'الاسم',
        ],
    ],

    'permission' => [
        'label' => 'صلاحية',
        'plural' => 'الصلاحيات',
        'fields' => [
            'name' => 'الاسم',
        ],
    ],
];
