<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Repositories\UserRepository;
use App\Services\JwtService;
use Throwable;

final class AuthMiddleware extends Controller
{
    /** @return array<string, mixed>|null */
    public function user(bool $respond = true): ?array
    {
        $token = $this->tokenFromRequest();
        if (!$token) {
            if ($respond) {
                $this->json(['success' => false, 'message' => 'Authentication token is required.'], 401);
            }
            return null;
        }

        try {
            $claims = (new JwtService())->verify($token);
            $user = (new UserRepository(Database::pdo()))->findById((int) ($claims['sub'] ?? 0));
            if (!$user) {
                if ($respond) {
                    $this->json(['success' => false, 'message' => 'Authenticated user not found.'], 401);
                }
                return null;
            }

            if (($user['status'] ?? '') !== 'ACTIVE') {
                if ($respond) {
                    $this->json(['success' => false, 'message' => 'User account is not active.'], 403);
                }
                return null;
            }

            return $user;
        } catch (Throwable $exception) {
            if ($respond) {
                $this->json(['success' => false, 'message' => $exception->getMessage()], 401);
            }
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    public function requirePageUser(): ?array
    {
        $user = $this->user(false);
        if ($user) {
            return $user;
        }

        $target = Router::basePath() . '/login';
        header('Location: ' . ($target === '' ? '/login' : $target));
        return null;
    }

    /** @return array<string, mixed>|null */
    public function requireRole(string $role): ?array
    {
        $user = $this->user();
        if (!$user) {
            return null;
        }

        if (($user['role'] ?? 'SUBSCRIBER') !== $role) {
            $this->json(['success' => false, 'message' => 'Forbidden. Required role: ' . $role], 403);
            return null;
        }

        return $user;
    }

    /** @return array<string, mixed>|null */
    public function requirePageRole(string $role): ?array
    {
        $user = $this->requirePageUser();
        if (!$user) {
            return null;
        }

        if (($user['role'] ?? 'SUBSCRIBER') !== $role) {
            http_response_code(403);
            echo 'Forbidden.';
            return null;
        }

        return $user;
    }

    private function tokenFromRequest(): ?string
    {
        $bearer = $this->bearerToken();
        if ($bearer) {
            return $bearer;
        }

        $cookie = $_COOKIE['shield_auth_token'] ?? null;
        return is_string($cookie) && $cookie !== '' ? $cookie : null;
    }
}
