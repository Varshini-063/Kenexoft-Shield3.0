<?php
declare(strict_types=1);

namespace App\Services;

final class TokenRevocationService
{
    public function revoke(string $token): void
    {
        $expiresAt = $this->expiresAt($token);
        if ($expiresAt <= time()) {
            return;
        }

        $data = $this->activeRevocations();
        $data[hash('sha256', $token)] = $expiresAt;
        file_put_contents($this->path(), json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function isRevoked(string $token): bool
    {
        $data = $this->activeRevocations();
        $hash = hash('sha256', $token);

        return isset($data[$hash]);
    }

    /** @return array<string, int> */
    private function activeRevocations(): array
    {
        $path = $this->path();
        $data = [];
        if (is_file($path)) {
            $decoded = json_decode(file_get_contents($path) ?: '{}', true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $now = time();
        $active = [];
        foreach ($data as $hash => $expiresAt) {
            if (is_string($hash) && (int) $expiresAt > $now) {
                $active[$hash] = (int) $expiresAt;
            }
        }

        if ($active !== $data) {
            file_put_contents($path, json_encode($active, JSON_PRETTY_PRINT), LOCK_EX);
        }

        return $active;
    }

    private function expiresAt(string $token): int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return 0;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        return is_array($payload) ? (int) ($payload['exp'] ?? 0) : 0;
    }

    private function path(): string
    {
        $directory = BASE_PATH . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory . DIRECTORY_SEPARATOR . 'revoked_tokens.json';
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
