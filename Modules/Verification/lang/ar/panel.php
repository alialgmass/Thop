<?php

return [
    'label' => 'طلب توثيق',
    'plural' => 'طلبات التوثيق',

    'columns' => [
        'business' => 'الشركة',
        'owner' => 'المالك',
        'governorate' => 'المحافظة',
        'docs' => 'المستندات',
        'status' => 'الحالة',
        'submitted_at' => 'تاريخ الإرسال',
        'reviewed_by' => 'راجعه',
        'not_submitted' => 'لم يُرسل',
    ],

    'sections' => [
        'business' => 'الشركة',
        'request' => 'الطلب',
        'documents' => 'المستندات',
    ],

    'fields' => [
        'company' => 'اسم الشركة',
        'activity' => 'النشاط',
        'owner_phone' => 'هاتف المالك',
        'owner_email' => 'بريد المالك',
        'contact_person' => 'مسؤول التواصل',
        'address' => 'العنوان',
        'reviewed_at' => 'تاريخ المراجعة',
        'rejection_reason' => 'سبب الرفض',
        'type' => 'النوع',
        'file' => 'الملف',
        'size' => 'الحجم',
        'download' => 'تنزيل',
        'reason_for_rejection' => 'سبب الرفض',
    ],

    'actions' => [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'approved' => 'تمت الموافقة على التوثيق.',
        'rejected' => 'تم رفض التوثيق.',
    ],
];
