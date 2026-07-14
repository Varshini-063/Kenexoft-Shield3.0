<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class JwtService
{
    private string $secret;
    private string $issuer;
    private int $ttl;

    public function __construct()
    {
        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $this->secret = (string) $config['jwt_secret'];
        $this->issuer = (string) $config['jwt_issuer'];
        $this->ttl = (int) $config['jwt_ttl'];

        if ($this->secret === 'change-this-secret-before-production' && ($config['env'] ?? 'local') === 'production') {
            throw new RuntimeException('JWT_SECRET must be configured before production use.');
        }
    }

    /** @param array<string, mixed> $claims */
    public function issue(array $claims): string
    {
        $now = time();
        $expiresAt = $now + $this->ttl;
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'jti' => $claims['jti'] ?? bin2hex(random_bytes(16)),
            'iat' => $now,
            'issued_at' => $now,
            'exp' => $expiresAt,
            'expires_at' => $expiresAt,
        ]);
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $segments[] = $this->base64UrlEncode(hash_hmac('sha256', implode('.', $segments), $this->secret, true));

        return implode('.', $segments);
    }

    /** @return array<string, mixed> */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token.');
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $this->secret, true));
        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid token payload.');
        }

        if ((int) ($decoded['exp'] ?? 0) < time()) {
            throw new RuntimeException('Token expired.');
        }

        if ((new TokenRevocationService())->isRevoked($token)) {
            throw new RuntimeException('Token revoked.');
        }

        return $decoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
