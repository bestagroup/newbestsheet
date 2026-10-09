<?php

return [
    'documents' => [
        'disk' => env('INVESTMENT_DOCUMENT_DISK', 'investment_documents'),
        'max_size_kb' => (int) env('INVESTMENT_DOCUMENT_MAX_SIZE_KB', 51200),
        'scanner' => env('DOCUMENT_SCANNER_BINARY', 'clamscan'),
        'antivirus_enabled' => (bool) env('INVESTMENT_DOCUMENT_ANTIVIRUS', false),
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip', 'video/mp4', 'audio/mpeg',
        ],
    ],
];
