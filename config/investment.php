<?php

return [
    'documents' => [
        'disk' => env('INVESTMENT_DOCUMENT_DISK', 'investment_documents'),
        'max_size_kb' => (int) env('INVESTMENT_DOCUMENT_MAX_SIZE_KB', 51200),
        'antivirus_enabled' => (bool) env('INVESTMENT_DOCUMENT_ANTIVIRUS', false),
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml',
            'application/zip',
        ],
    ],
];
