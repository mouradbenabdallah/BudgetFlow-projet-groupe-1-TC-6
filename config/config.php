<?php

/**
 * Application configuration.
 *
 * All sensitive values are read from environment variables (set in docker-compose.yml).
 * Hard-coded fallbacks are safe defaults for local development only.
 *
 * Keys:
 *   app      — name, environment, public URL, timezone
 *   database — PostgreSQL connection (host must be "postgres" inside Docker)
 *   mail     — Gmail SMTP via STARTTLS on port 587;
 *              MAIL_PASSWORD must be a Gmail App Password, not your account password
 */
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
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'budgetflow',
        'user' => getenv('DB_USER') ?: 'budgetflow',
        'password' => getenv('DB_PASSWORD') ?: 'budgetflow',
    ],
    'ollama' => [
        // Ollama tourne dans le service Docker "ollama" — ne pas utiliser localhost ici.
        'host'  => getenv('OLLAMA_HOST') ?: 'http://ollama:11434',
        'model' => getenv('OLLAMA_MODEL') ?: 'llama3.2:1b',
    ],
    'mail' => [
        // Gmail SMTP : STARTTLS port 587.
        // MAIL_PASSWORD doit être un "mot de passe d'application" Gmail (pas votre mot de passe Google).
        // Générer : myaccount.google.com → Sécurité → Mots de passe des applications
        'host'       => getenv('MAIL_HOST')       ?: 'smtp.gmail.com',
        'port'       => getenv('MAIL_PORT')       ?: '587',
        'username'   => getenv('MAIL_USERNAME')   ?: '',
        'password'   => getenv('MAIL_PASSWORD')   ?: '',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_USERNAME') ?: '',
        'from_name'  => getenv('MAIL_FROM_NAME')  ?: 'BudgetFlow',
    ],
];
