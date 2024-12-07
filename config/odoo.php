<?php

return [
    // URL based on environment
    'url' => env('APP_ENV') === 'production'
        ? env('ODOO_URL_PROD', 'https://tadawi.odoo.com')
        : env('ODOO_URL_STAGING', 'https://tadawi.odoo.com'),

    // Database based on environment
    'db' => env('APP_ENV') === 'production'
        ? env('ODOO_DB_PROD', 'aeltinai-fs-tadawi-main-13942389')
        : env('ODOO_DB_STAGING', 'tadawi-staging-15283379'),

    // Username based on environment
    'username' => env('APP_ENV') === 'production'
        ? env('ODOO_USERNAME_PROD', 'bashar.g@tadawipharmacy.com')
        : env('ODOO_USERNAME_STAGING', 'bashar.g@tadawipharmacy.com'),

    // Password based on environment
    'password' => env('APP_ENV') === 'production'
        ? env('ODOO_PASSWORD_PROD', 'Diaa@2024')
        : env('ODOO_PASSWORD_STAGING', 'Diaa@2024'),
        
    // SSL verification
    'verify_ssl' => env('ODOO_VERIFY_SSL', true),
];