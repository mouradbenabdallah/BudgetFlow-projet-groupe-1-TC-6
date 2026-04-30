<?php

return [
    'app' => [
        'name' => 'BudgetFlow',
        // Ces valeurs peuvent être changées depuis docker-compose.yml via les variables APP_*.
        'env' => getenv('APP_ENV') ?: 'local',
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Africa/Tunis',
    ],
    'database' => [
        // Dans Docker, DB_HOST doit rester "postgres" : c'est le nom du service Compose.
        'host' => getenv('DB_HOST') ?: 'postgres',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'budgetflow',
        'user' => getenv('DB_USER') ?: 'budgetflow',
        'password' => getenv('DB_PASSWORD') ?: 'budgetflow',
    ],
    'mail' => [
        // Prévu pour PHPMailer + Resend plus tard. Fonction 1 ne l'utilise pas encore.
        'host' => getenv('MAIL_HOST') ?: '',
        'port' => getenv('MAIL_PORT') ?: '587',
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@budgetflow.local',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'BudgetFlow',
    ],
];
