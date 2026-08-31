<?php

declare(strict_types=1);

return [
    // Runtime bootstrap: these values must remain outside GitHub.
    'db_host' => 'db.example.ionos.de',
    'db_port' => 3306,
    'db_name' => 'nerozon_dispatcher',
    'db_user' => 'nerozon_dispatcher',
    'db_password' => 'SET_DATABASE_PASSWORD_HERE',

    // One-time/idempotent init access. Remove or rotate after provisioning.
    'init_key' => 'SET_LONG_RANDOM_INIT_KEY_HERE',

    // Optional: only enable when the IONOS account may CREATE DATABASE/USER.
    // On managed hosting, database/user may need to be created in the IONOS panel.
    'provision_database_access' => false,
    'provision_user' => '',
    'provision_password' => '',
    'db_user_host' => '%',

    // When true, init also executes CREATE DATABASE IF NOT EXISTS with db_user.
    // Leave false when the database is provisioned externally.
    'create_database_if_possible' => false,
];
