<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AuditLogRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\UserRepository;
use RuntimeException;
use Throwable;

final class AuthService
{
    /** @param array<string, mixed> $payload @param array<string, mixed>|null $cvFile @return array<string, mixed> */
    public function register(string $persona, array $payload, ?array $cvFile = null): array
    {
        $pdo = Database::pdo();
        $userRepository = new UserRepository($pdo);
        $email = $this->emailFromPayload($persona, $payload);

        if ($userRepository->findByEmail($email)) {
            throw new RuntimeException('A user with this email already exists.');
        }

        $passwordHash = password_hash((string) ($payload['security']['password'] ?? ''), PASSWORD_BCRYPT);
        $result = (new RegistrationRepository($pdo))->register($persona, $payload, $passwordHash, $cvFile);
        $token = $this->issueUserToken($result['user']);

        return [
            'referenceId' => $result['referenceId'],
            'token' => $token,
            'user' => $result['user'],
        ];
    }

    /** @return array<string, mixed> */
    public function login(string $email, string $password, string $role): array
    {
        $pdo = Database::pdo();
        $userRepository = new UserRepository($pdo);
        $auditRepository = new AuditLogRepository($pdo);
        $email = strtolower(trim($email));
        $role = strtoupper(trim($role));
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $userRepository->findByEmailAndRole($email, $role) : null;

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $auditRepository->log($user ? (int) $user['id'] : null, 'AUTH_LOGIN_FAILURE', 'Login failed.');
            throw new RuntimeException('Invalid email or password.');
        }

        if (($user['status'] ?? '') !== 'ACTIVE') {
            $auditRepository->log((int) $user['id'], 'AUTH_LOGIN_FAILURE', 'Login rejected for non-active account.');
            throw new RuntimeException('Only ACTIVE users may login.');
        }

        $auditRepository->log((int) $user['id'], 'AUTH_LOGIN_SUCCESS', 'User logged in.');

        return [
            'token' => $this->issueUserToken($user),
            'user' => $userRepository->publicUser($user),
        ];
    }

    /** @return array{token: string|null} */
    public function requestPasswordReset(string $email): array
    {
        $pdo = Database::pdo();
        $userRepository = new UserRepository($pdo);
        $auditRepository = new AuditLogRepository($pdo);
        $email = strtolower(trim($email));
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $userRepository->findByEmail($email) : null;

        if (!$user || ($user['status'] ?? '') !== 'ACTIVE') {
            $auditRepository->log($user ? (int) $user['id'] : null, 'PASSWORD_RESET_REQUEST', 'Password reset requested.');
            return ['token' => null];
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        (new PasswordResetRepository($pdo))->create((int) $user['id'], $tokenHash);
        $auditRepository->log((int) $user['id'], 'PASSWORD_RESET_REQUEST', 'Password reset token generated.');

        return ['token' => $plainToken];
    }

    public function resetPassword(string $token, string $password): void
    {
        $pdo = Database::pdo();
        $resetRepository = new PasswordResetRepository($pdo);
        $record = $resetRepository->findValid(hash('sha256', trim($token)));

        if (!$record || ($record['status'] ?? '') !== 'ACTIVE') {
            throw new RuntimeException('Password reset link is invalid or expired.');
        }

        $pdo->beginTransaction();
        try {
            (new UserRepository($pdo))->updatePassword((int) $record['user_id'], password_hash($password, PASSWORD_BCRYPT));
            $resetRepository->markUsed((int) $record['id']);
            (new AuditLogRepository($pdo))->log((int) $record['user_id'], 'PASSWORD_RESET_SUCCESS', 'Password was reset successfully.');
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array<string, string> */
    public function passwordErrors(string $password, string $confirmPassword): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character';
        }

        if ($confirmPassword === '') {
            $errors['confirmPassword'] = 'Please confirm your password';
        } elseif ($password !== $confirmPassword) {
            $errors['confirmPassword'] = 'Passwords do not match';
        }

        return $errors;
    }

    /** @param array<string, mixed> $user */
    private function issueUserToken(array $user): string
    {
        $userId = (int) $user['id'];

        return (new JwtService())->issue([
            'sub' => $userId,
            'user_id' => $userId,
            'email' => $user['email'],
            'role' => $user['role'] ?? 'SUBSCRIBER',
            'persona' => $user['persona'],
            'status' => $user['status'] ?? 'ACTIVE',
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function emailFromPayload(string $persona, array $payload): string
    {
        return $persona === 'IT_CONSULTANT'
            ? (string) ($payload['email'] ?? '')
            : (string) ($payload['contactEmail'] ?? '');
    }
}
