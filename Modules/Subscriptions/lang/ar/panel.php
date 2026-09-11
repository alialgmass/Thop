<?php

return [
    'plan' => [
        'label' => 'خطة اشتراك',
        'plural' => 'خطط الاشتراك',
    ],
    'subscription' => [
        'label' => 'اشتراك',
        'plural' => 'الاشتراكات',
    ],

    'sections' => [
        'plan_details' => 'تفاصيل الخطة',
        'entitlements' => 'الصلاحيات',
        'entitlements_hint' => 'أزواج مفتاح/قيمة — يقدر الأدمن يعدّلها من غير migration (MNT-NFR-02).',
        'business' => 'الشركة',
        'subscription' => 'الاشتراك',
    ],

    'fields' => [
        'name' => 'الاسم',
        'account_type' => 'نوع الحساب',
        'price' => 'السعر',
        'price_custom_hint' => 'اتركه فارغًا للتسعير المخصص',
        'billing_cycle' => 'دورة الفوترة',
        'cycle' => 'الدورة',
        'trial_days' => 'مدة التجربة (أيام)',
        'is_active' => 'مُفعّلة',
        'entitlements' => 'الصلاحيات',
        'entitlement_key' => 'المفتاح',
        'entitlement_value' => 'القيمة',
        'entitlement_key_hint' => 'مثال: product_limit',
        'entitlement_value_hint' => 'مثال: Large أو true أو 100',
        'add_entitlement' => 'إضافة صلاحية',
        'custom' => 'مخصص',
        'none' => 'بدون',
        'company' => 'اسم الشركة',
        'activity' => 'النشاط',
        'owner_phone' => 'هاتف المالك',
        'owner_email' => 'بريد المالك',
        'plan' => 'الخطة',
        'status' => 'الحالة',
        'period_ends' => 'نهاية الفترة',
        'trial_ends' => 'نهاية التجربة',
        'subscribed_since' => 'مشترك منذ',
        'new_period_end' => 'تاريخ نهاية الفترة الجديد',
    ],

    'account_types' => [
        'importer' => 'مستورد',
        'wholesaler' => 'تاجر جملة',
        'retailer' => 'تاجر تجزئة',
    ],

    'billing_cycles' => [
        'monthly' => 'شهري',
        'annual' => 'سنوي',
    ],

    'actions' => [
        'grant_trial' => 'منح تجربة / ترويج',
        'extend_period' => 'تمديد الفترة',
        'cancel' => 'إلغاء الاشتراك',
        'trial_granted' => 'تم منح التجربة / الترويج.',
        'period_extended' => 'تم تمديد فترة الاشتراك.',
        'cancelled' => 'تم إلغاء الاشتراك.',
        'apply_to_existing' => 'تطبيق على الاشتراكات الحالية',
        'apply_to_existing_confirm' => 'أفهم أن هذا يعيد كتابة صلاحيات كل مشترك مفعّل في هذه الخطة الآن.',
        'apply_to_existing_done' => 'تم التطبيق على :count اشتراك.',
    ],
];
