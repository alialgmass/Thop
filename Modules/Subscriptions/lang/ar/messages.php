<?php

return [
    'statuses' => [
        'active' => 'نشط',
        'expired' => 'منتهي',
        'cancelled' => 'ملغي',
        'restricted' => 'مقيد',
    ],
    'billing_cycle' => [
        'monthly' => 'شهري',
        'annual' => 'سنوي',
    ],
    'messages' => [
        'plans_retrieved' => 'تم استرجاع خطط الاشتراك بنجاح',
        'already_subscribed' => 'لديك اشتراك نشط بالفعل',
        'subscribed' => 'تم الاشتراك بنجاح',
        'unauthorized' => 'غير مصرح لك بالوصول لهذا الاشتراك',
        'subscription_not_active' => 'الاشتراك غير نشط',
        'subscription_updated' => 'تم تحديث الاشتراك بنجاح',
        'usage_retrieved' => 'تم استرجاع بيانات الاستخدام بنجاح',
        'subscription_retrieved' => 'تم استرجاع الاشتراك بنجاح',
        'plan_type_mismatch' => 'هذه الخطة غير متاحة لنوع حسابك',
    ],
    'validation' => [
        'plan_required' => 'يجب تحديد خطة الاشتراك',
        'plan_not_found' => 'خطة الاشتراك غير موجودة أو غير نشطة',
        'action_required' => 'يجب تحديد الإجراء (upgrade / downgrade / cancel)',
        'action_invalid' => 'الإجراء غير صالح',
        'plan_required_for_action' => 'يجب تحديد خطة الاشتراك لهذا الإجراء',
    ],
];
