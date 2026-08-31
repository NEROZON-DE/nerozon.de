<?php

declare(strict_types=1);

return [
    'admin_user' => 'rainer',
    'admin_password_hash' => 'SET_PASSWORD_HASH_HERE',

    'ingest_token' => 'SET_LONG_RANDOM_TOKEN_HERE',
    'cron_token' => 'SET_LONG_RANDOM_TOKEN_HERE',

    'openai_api_key' => 'SET_OPENAI_API_KEY_HERE',
    'openai_base_url' => 'https://api.openai.com/v1',
    'default_provider' => 'openai',
    'default_model' => 'gpt-5.6-luna',

    // Empfehlung: außerhalb des Webroots halten.
    'data_dir' => dirname(__DIR__, 2) . '/../dispatcher-data',
    'max_jobs_per_cron' => 5,
    'max_retries' => 2,
];
