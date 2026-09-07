<?php

return [
    'label' => 'Verification request',
    'plural' => 'Verification requests',

    'columns' => [
        'business' => 'Business',
        'owner' => 'Owner',
        'governorate' => 'Governorate',
        'docs' => 'Docs',
        'status' => 'Status',
        'submitted_at' => 'Submitted at',
        'reviewed_by' => 'Reviewed by',
        'not_submitted' => 'Not submitted',
    ],

    'sections' => [
        'business' => 'Business',
        'request' => 'Request',
        'documents' => 'Documents',
    ],

    'fields' => [
        'company' => 'Company',
        'activity' => 'Activity',
        'owner_phone' => 'Owner phone',
        'owner_email' => 'Owner email',
        'contact_person' => 'Contact person',
        'address' => 'Address',
        'reviewed_at' => 'Reviewed at',
        'rejection_reason' => 'Rejection reason',
        'type' => 'Type',
        'file' => 'File',
        'size' => 'Size',
        'download' => 'Download',
        'reason_for_rejection' => 'Reason for rejection',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'approved' => 'Verification approved.',
        'rejected' => 'Verification rejected.',
    ],
];
