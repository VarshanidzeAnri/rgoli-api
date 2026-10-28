<?php

return [
    'version' => env('RGOLI_VERSION', 'v5'),

    /*
     * Demo mode shows quick-login accounts on the login page and enables POST /admin/reset-demo.
     */
    'demo' => (bool) env('APP_DEMO', false),

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'uploads' => [
        'disk' => env('UPLOADS_DISK', 'local'),
        'max_kb' => (int) env('UPLOADS_MAX_KB', 51200),
        'extensions' => explode(',', (string) env('UPLOADS_EXTENSIONS', 'pdf,dwg,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z')),
        'max_files' => (int) env('UPLOADS_MAX_FILES', 10),
    ],

    'demo_accounts' => [
        ['email' => 'admin@rgoli.ge', 'password' => 'admin123', 'role' => 'owner'],
        ['email' => 'manager@rgoli.ge', 'password' => 'manager123', 'role' => 'project_manager'],
        ['email' => 'designer@rgoli.ge', 'password' => 'designer123', 'role' => 'interior_designer'],
        ['email' => 'finance@rgoli.ge', 'password' => 'finance123', 'role' => 'finance_manager'],
    ],
];
