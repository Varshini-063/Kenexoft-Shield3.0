<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'mysql' => [
        'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
        'port' => (string) Env::get('DB_PORT', '3306'),
        'database' => (string) Env::get('DB_DATABASE', 'shield3'),
        'username' => (string) Env::get('DB_USERNAME', 'root'),
        'password' => (string) Env::get('DB_PASSWORD', ''),
    ],
];
