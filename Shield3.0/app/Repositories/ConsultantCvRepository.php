<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ConsultantCvRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @param array<string, mixed> $metadata */
    public function save(int $userId, array $metadata): int
    {
        $this->delete($userId);
        $statement = $this->pdo->prepare(
            'INSERT INTO consultant_cv (
                user_id, original_filename, stored_filename, file_path, file_size, mime_type, uploaded_at
            ) VALUES (
                :user_id, :original_filename, :stored_filename, :file_path, :file_size, :mime_type, NOW()
            )'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':original_filename' => $metadata['original_filename'],
            ':stored_filename' => $metadata['stored_filename'],
            ':file_path' => $metadata['file_path'],
            ':file_size' => $metadata['file_size'],
            ':mime_type' => $metadata['mime_type'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM consultant_cv WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $statement->execute([':user_id' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function delete(int $userId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM consultant_cv WHERE user_id = :user_id');
        $statement->execute([':user_id' => $userId]);
    }
}
