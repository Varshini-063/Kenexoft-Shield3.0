<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReferenceDataRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public function managedServices(): array
    {
        return $this->pdo->query('SELECT id, name FROM managed_services ORDER BY name')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function expertise(): array
    {
        return $this->pdo->query('SELECT id, name FROM expertise ORDER BY name')->fetchAll();
    }

    public function serviceIdByName(string $name): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM managed_services WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function expertiseIdByName(string $name): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM expertise WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @param array<int, string> $services */
    public function replaceUserServices(int $userId, array $services, ?string $otherService): void
    {
        $this->pdo->prepare('DELETE FROM user_services WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        $statement = $this->pdo->prepare('INSERT INTO user_services (user_id, service_id, other_service) VALUES (:user_id, :service_id, :other_service)');

        foreach ($services as $service) {
            if ($service === 'Others') {
                continue;
            }
            $statement->execute([
                ':user_id' => $userId,
                ':service_id' => $this->serviceIdByName($service),
                ':other_service' => null,
            ]);
        }

        if ($otherService !== null && trim($otherService) !== '') {
            $statement->execute([
                ':user_id' => $userId,
                ':service_id' => null,
                ':other_service' => trim($otherService),
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function userServices(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT us.id, ms.name, us.other_service
             FROM user_services us
             LEFT JOIN managed_services ms ON ms.id = us.service_id
             WHERE us.user_id = :user_id
             ORDER BY COALESCE(ms.name, us.other_service)'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }

    /** @param array<int, string> $expertise */
    public function replaceConsultantExpertise(int $userId, array $expertise, ?string $otherExpertise): void
    {
        $this->pdo->prepare('DELETE FROM consultant_expertise WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        $statement = $this->pdo->prepare('INSERT INTO consultant_expertise (user_id, expertise_id, other_expertise) VALUES (:user_id, :expertise_id, :other_expertise)');

        foreach ($expertise as $item) {
            if ($item === 'Others') {
                continue;
            }
            $statement->execute([
                ':user_id' => $userId,
                ':expertise_id' => $this->expertiseIdByName($item),
                ':other_expertise' => null,
            ]);
        }

        if ($otherExpertise !== null && trim($otherExpertise) !== '') {
            $statement->execute([
                ':user_id' => $userId,
                ':expertise_id' => null,
                ':other_expertise' => trim($otherExpertise),
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function consultantExpertise(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ce.id, e.name, ce.other_expertise
             FROM consultant_expertise ce
             LEFT JOIN expertise e ON e.id = ce.expertise_id
             WHERE ce.user_id = :user_id
             ORDER BY COALESCE(e.name, ce.other_expertise)'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }
}
