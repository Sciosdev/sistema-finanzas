<?php

return [
    // Versión visible de la app (semver). Fuente única: se muestra en el menú
    // lateral y en el footer. SÚBELA en cada cambio que se despliegue para
    // confirmar a simple vista que el deploy llegó. Ver CLAUDE.md.
    'version' => '2.14.0',

    'external_backup_path' => env('FINANCE_EXTERNAL_BACKUP_PATH'),

    'owner_email' => env('FINANCE_OWNER_EMAIL'),

    'health_token' => env('FINANCE_HEALTH_TOKEN'),

    'expected_app_url' => env('FINANCE_EXPECTED_APP_URL', 'https://finanzas.xaanal.com'),

    'deployment' => [
        // URL HTTPS de cPanel con el puerto seguro de UAPI.
        'cpanel_url' => env('FINANCE_CPANEL_URL'),
        'cpanel_username' => env('FINANCE_CPANEL_USERNAME'),
        'cpanel_api_token' => env('FINANCE_CPANEL_API_TOKEN'),
        'repository_root' => env('FINANCE_CPANEL_REPOSITORY_ROOT'),
        'branch' => env('FINANCE_CPANEL_BRANCH', 'main'),

        // Token independiente para GET/POST /api/finance/deployment/*.
        'agent_api_token' => env('FINANCE_DEPLOY_API_TOKEN'),

        'connect_timeout' => (int) env('FINANCE_DEPLOY_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('FINANCE_DEPLOY_TIMEOUT', 120),
    ],

    'advisor' => [
        // Token exclusivo para GET /api/finance/advisor/snapshot.
        'api_token' => env('FINANCE_ADVISOR_API_TOKEN'),
        'history_days' => (int) env('FINANCE_ADVISOR_HISTORY_DAYS', 90),
        'horizon_days' => (int) env('FINANCE_ADVISOR_HORIZON_DAYS', 45),
        'transaction_limit' => (int) env('FINANCE_ADVISOR_TRANSACTION_LIMIT', 60),
        'include_descriptions' => filter_var(
            env('FINANCE_ADVISOR_INCLUDE_DESCRIPTIONS', true),
            FILTER_VALIDATE_BOOL
        ),
    ],
];
