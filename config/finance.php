<?php

return [
    // Versión visible de la app (semver). Fuente única: se muestra en el menú
    // lateral y en el footer. SÚBELA en cada cambio que se despliegue para
    // confirmar a simple vista que el deploy llegó. Ver CLAUDE.md.
    'version' => '2.10.2',

    'external_backup_path' => env('FINANCE_EXTERNAL_BACKUP_PATH'),

    'owner_email' => env('FINANCE_OWNER_EMAIL'),

    'health_token' => env('FINANCE_HEALTH_TOKEN'),

    'expected_app_url' => env('FINANCE_EXPECTED_APP_URL', 'https://finanzas.xaanal.com'),
];
