<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (
                persona, first_name, last_name, email, mobile, password_hash, gstin, status, role, created_at, updated_at
            ) VALUES (
                :persona, :first_name, :last_name, :email, :mobile, :password_hash, :gstin, :status, :role, NOW(), NOW()
            )'
        );
        $statement->execute([
            ':persona' => $data['persona'],
            ':first_name' => $data['first_name'] ?? null,
            ':last_name' => $data['last_name'] ?? null,
            ':email' => $data['email'],
            ':mobile' => $data['mobile'],
            ':password_hash' => $data['password_hash'],
            ':gstin' => $data['gstin'] ?: null,
            ':status' => $data['status'] ?? 'ACTIVE',
            ':role' => $data['role'] ?? 'SUBSCRIBER',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => $email]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByEmailAndRole(string $email, string $role): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1');
        $statement->execute([
            ':email' => $email,
            ':role' => $role,
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getSubscribers(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE role = :role
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute([':role' => 'SUBSCRIBER']);

        return array_map(fn(array $user): array => $this->publicUser($user), $statement->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function findSubscriberById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE id = :id
               AND role = :role
             LIMIT 1'
        );
        $statement->execute([
            ':id' => $id,
            ':role' => 'SUBSCRIBER',
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $this->publicUser($row) : null;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $allowed = ['first_name', 'last_name', 'mobile', 'gstin', 'status'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = $field . ' = :' . $field;
                $params[':' . $field] = $data[$field];
            }
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'updated_at = NOW()';
        $statement = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $statement->execute($params);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            ':id' => $id,
            ':password_hash' => $passwordHash,
        ]);
    }

    /** @param array<string, mixed> $user @return array<string, mixed> */
    public function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'persona' => $user['persona'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'gstin' => $user['gstin'],
            'status' => $user['status'],
            'role' => $user['role'] ?? 'SUBSCRIBER',
            'createdAt' => $user['created_at'] ?? null,
            'updatedAt' => $user['updated_at'] ?? null,
        ];
    }
}
