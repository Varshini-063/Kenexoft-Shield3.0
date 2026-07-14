<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AddressRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @param array<string, mixed> $address */
    public function create(int $userId, string $type, array $address): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO addresses (
                user_id, address_type, address_line_1, address_line_2, landmark, city, state, country, postal_code, created_at, updated_at
            ) VALUES (
                :user_id, :address_type, :address_line_1, :address_line_2, :landmark, :city, :state, :country, :postal_code, NOW(), NOW()
            )'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':address_type' => $type,
            ':address_line_1' => $address['addressLine1'] ?? $address['address_line_1'] ?? '',
            ':address_line_2' => $address['addressLine2'] ?? $address['address_line_2'] ?? null,
            ':landmark' => $address['landmark'] ?? null,
            ':city' => $address['city'] ?? '',
            ':state' => $address['state'] ?? '',
            ':country' => $address['country'] ?? '',
            ':postal_code' => $address['postalCode'] ?? $address['postal_code'] ?? '',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM addresses WHERE user_id = :user_id ORDER BY id DESC');
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }

    /** @param array<string, mixed> $address */
    public function update(int $id, int $userId, array $address): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE addresses SET
                address_type = :address_type,
                address_line_1 = :address_line_1,
                address_line_2 = :address_line_2,
                landmark = :landmark,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :postal_code,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':address_type' => $address['addressType'] ?? $address['address_type'] ?? 'REGISTERED_OFFICE',
            ':address_line_1' => $address['addressLine1'] ?? $address['address_line_1'] ?? '',
            ':address_line_2' => $address['addressLine2'] ?? $address['address_line_2'] ?? null,
            ':landmark' => $address['landmark'] ?? null,
            ':city' => $address['city'] ?? '',
            ':state' => $address['state'] ?? '',
            ':country' => $address['country'] ?? '',
            ':postal_code' => $address['postalCode'] ?? $address['postal_code'] ?? '',
        ]);
    }

    public function delete(int $id, int $userId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM addresses WHERE id = :id AND user_id = :user_id');
        $statement->execute([':id' => $id, ':user_id' => $userId]);
    }
}
