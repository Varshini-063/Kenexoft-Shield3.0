<?php
declare(strict_types=1);

namespace App\Services;

final class RateLimiter
{
    public function tooManyAttempts(string $key, int $maxAttempts = 20, int $windowSeconds = 60): bool
    {
        $path = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate_limit.json';
        $now = time();
        $data = [];

        if (is_file($path)) {
            $decoded = json_decode(file_get_contents($path) ?: '{}', true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $attempts = array_values(array_filter($data[$key] ?? [], static fn(int $timestamp): bool => $timestamp > $now - $windowSeconds));
        $attempts[] = $now;
        $data[$key] = $attempts;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

        return count($attempts) > $maxAttempts;
    }
}
