<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\AuditLogRepository;
use App\Services\AuthService;
use App\Services\CsrfService;
use App\Services\RateLimiter;
use App\Services\RegistrationValidator;
use App\Services\TokenRevocationService;
use Throwable;

final class AuthController extends Controller
{
    public function csrf(): void
    {
        $this->json([
            'success' => true,
            'token' => (new CsrfService())->token(),
        ]);
    }

    public function register(): void
    {
        if ((new RateLimiter())->tooManyAttempts('register:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 12, 60)) {
            $this->json(['success' => false, 'message' => 'Too many registration attempts.'], 429);
            return;
        }

        if (!(new CsrfService())->validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
            return;
        }

        [$persona, $payload, $cvFile] = $this->registrationPayload();
        $persona = RegistrationValidator::normalisePersona($persona);
        $errors = (new RegistrationValidator())->validate($persona, $payload, $cvFile);

        if ($errors !== []) {
            $this->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $errors,
            ], 422);
            return;
        }

        try {
            $result = (new AuthService())->register($persona, $payload, $cvFile);
            $this->json([
                'success' => true,
                'referenceId' => $result['referenceId'],
                'token' => $result['token'],
                'user' => $result['user'],
                'data' => $payload,
                'message' => 'Registration completed successfully.',
            ], 201);
        } catch (Throwable $exception) {
            $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => ['server' => $exception->getMessage()],
            ], 500);
        }
    }

    public function login(): void
    {
        if ((new RateLimiter())->tooManyAttempts('login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 20, 60)) {
            $this->json(['success' => false, 'message' => 'Too many login attempts.'], 429);
            return;
        }

        if (!(new CsrfService())->validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
            return;
        }

        $input = $this->jsonInput();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $role = strtoupper(trim((string) ($input['role'] ?? '')));
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if (!in_array($role, ['SUBSCRIBER', 'SUPER_ADMIN'], true)) {
            $errors['role'] = 'Select a valid login role.';
        }

        if ($errors !== []) {
            $this->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $errors,
            ], 422);
            return;
        }

        try {
            $result = (new AuthService())->login($email, $password, $role);
            $this->setAuthCookie($result['token'], (bool) ($input['remember'] ?? false));
            $redirectUrl = $this->dashboardUrl((string) $result['user']['role']);
            $this->json([
                'success' => true,
                'token' => $result['token'],
                'role' => $result['user']['role'],
                'user' => $result['user'],
                'redirect' => $redirectUrl,
                'redirectUrl' => $redirectUrl,
            ]);
        } catch (Throwable $exception) {
            $statusCode = $exception->getMessage() === 'Only ACTIVE users may login.' ? 403 : 401;
            $this->json(['success' => false, 'message' => $exception->getMessage()], $statusCode);
        }
    }

    public function logout(): void
    {
        if (!(new CsrfService())->validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->clearAuthCookie();
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
            return;
        }

        $token = $this->tokenFromRequest();
        $user = (new AuthMiddleware())->user(false);

        if ($user) {
            try {
                (new AuditLogRepository(Database::pdo()))->log((int) $user['id'], 'AUTH_LOGOUT', 'User logged out.');
            } catch (Throwable) {
            }
        }

        if ($token) {
            (new TokenRevocationService())->revoke($token);
        }

        $this->clearAuthCookie();
        $this->json([
            'success' => true,
            'message' => 'Logged out.',
            'redirectUrl' => Router::basePath() . '/login',
        ]);
    }

    public function forgotPassword(): void
    {
        if ((new RateLimiter())->tooManyAttempts('forgot:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60)) {
            $this->json(['success' => false, 'message' => 'Too many password reset attempts.'], 429);
            return;
        }

        if (!(new CsrfService())->validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
            return;
        }

        $input = $this->jsonInput();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => ['email' => 'Enter a valid email address.'],
            ], 422);
            return;
        }

        $result = (new AuthService())->requestPasswordReset($email);
        $payload = [
            'success' => true,
            'message' => 'If an active account exists for that email, a password reset link has been prepared.',
        ];

        if ($result['token']) {
            $payload['resetUrl'] = Router::basePath() . '/reset-password?token=' . rawurlencode($result['token']);
        }

        $this->json($payload);
    }

    public function resetPassword(): void
    {
        if ((new RateLimiter())->tooManyAttempts('reset:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 12, 60)) {
            $this->json(['success' => false, 'message' => 'Too many password reset attempts.'], 429);
            return;
        }

        if (!(new CsrfService())->validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
            return;
        }

        $input = $this->jsonInput();
        $token = trim((string) ($input['token'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $confirmPassword = (string) ($input['confirmPassword'] ?? '');
        $errors = (new AuthService())->passwordErrors($password, $confirmPassword);

        if ($token === '') {
            $errors['token'] = 'Password reset token is required.';
        }

        if ($errors !== []) {
            $this->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $errors,
            ], 422);
            return;
        }

        try {
            (new AuthService())->resetPassword($token, $password);
            $this->json([
                'success' => true,
                'message' => 'Password reset successful. You can now log in.',
                'redirectUrl' => Router::basePath() . '/login',
            ]);
        } catch (Throwable $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    /** @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>|null} */
    private function registrationPayload(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains(strtolower($contentType), 'multipart/form-data')) {
            $payload = json_decode((string) ($_POST['payload'] ?? '{}'), true);

            return [
                (string) ($_POST['persona'] ?? ''),
                is_array($payload) ? $payload : [],
                $_FILES['cv'] ?? null,
            ];
        }

        $input = $this->jsonInput();

        return [
            (string) ($input['persona'] ?? ''),
            is_array($input['payload'] ?? null) ? $input['payload'] : [],
            null,
        ];
    }

    private function setAuthCookie(string $token, bool $remember): void
    {
        setcookie('shield_auth_token', $token, [
            'expires' => $remember ? time() + 86400 : 0,
            'path' => $this->cookiePath(),
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearAuthCookie(): void
    {
        setcookie('shield_auth_token', '', [
            'expires' => time() - 3600,
            'path' => $this->cookiePath(),
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function cookiePath(): string
    {
        $basePath = Router::basePath();
        return $basePath === '' ? '/' : $basePath . '/';
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) === '443');
    }

    private function tokenFromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        $cookie = $_COOKIE['shield_auth_token'] ?? null;
        return is_string($cookie) && $cookie !== '' ? $cookie : null;
    }

    private function dashboardUrl(string $role): string
    {
        $path = match ($role) {
            'SUPER_ADMIN' => '/admin/dashboard',
            'SUBSCRIBER' => '/dashboard',
            default => '/dashboard',
        };

        return Router::basePath() . $path;
    }
}
