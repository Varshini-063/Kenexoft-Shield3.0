<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'env' => (string) Env::get('APP_ENV', 'local'),
    'url' => (string) Env::get('APP_URL', 'http://localhost/Shield3.0'),
    'jwt_secret' => (string) Env::get('JWT_SECRET', 'change-this-secret-before-production'),
    'jwt_issuer' => (string) Env::get('JWT_ISSUER', 'kenexoft-shield'),
    'jwt_ttl' => (int) Env::get('JWT_TTL', 86400),
    'cv_upload_path' => BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cv',
    'cv_max_bytes' => 10 * 1024 * 1024,
];
