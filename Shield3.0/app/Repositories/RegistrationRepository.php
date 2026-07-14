<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\CVUploadService;
use App\Services\GSTService;
use PDO;
use Throwable;

final class RegistrationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @param array<string, mixed> $payload @param array<string, mixed>|null $cvFile @return array<string, mixed> */
    public function register(string $persona, array $payload, string $passwordHash, ?array $cvFile = null): array
    {
        $storedCv = null;
        $this->pdo->beginTransaction();

        try {
            $userRepository = new UserRepository($this->pdo);
            $addressRepository = new AddressRepository($this->pdo);
            $referenceRepository = new ReferenceDataRepository($this->pdo);
            $cvRepository = new ConsultantCvRepository($this->pdo);
            $auditRepository = new AuditLogRepository($this->pdo);

            $userId = $userRepository->create($this->userData($persona, $payload, $passwordHash));
            $registeredAddress = $this->registeredAddressFor($persona, $payload);
            $addressRepository->create($userId, 'REGISTERED_OFFICE', $registeredAddress);

            $subscriptionAddress = $registeredAddress;
            $billingAddress = $registeredAddress;

            if ($persona === 'MSP' || $persona === 'COMPANY') {
                $companyId = $this->createCompany($userId, $persona, $payload);
                [$subscriptionAddress, $billingAddress, $selectedBranchId] = $this->createBranches($companyId, $userId, $payload, $registeredAddress);
                $this->createSubscriptionSettings($userId, $payload, $selectedBranchId);

                if ($persona === 'MSP') {
                    $referenceRepository->replaceUserServices($userId, $payload['services'] ?? [], $payload['customServices'] ?? null);
                }
            }

            if ($persona === 'IT_CONSULTANT') {
                $referenceRepository->replaceConsultantExpertise($userId, $payload['expertise'] ?? [], $payload['customExpertise'] ?? null);
                if ($cvFile) {
                    $storedCv = (new CVUploadService())->uploadCV($cvFile, $userId);
                    $cvRepository->save($userId, $storedCv);
                }
            }

            $this->createGstClassification($userId, $registeredAddress, $subscriptionAddress, $billingAddress);
            $auditRepository->log($userId, 'AUTH_REGISTER', 'User registered as ' . $persona);
            $this->pdo->commit();

            $user = $userRepository->findById($userId);

            return [
                'referenceId' => $this->referenceId($persona, $userId),
                'user' => $user ? $userRepository->publicUser($user) : ['id' => $userId],
            ];
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            if ($storedCv && is_file((string) $storedCv['file_path'])) {
                unlink((string) $storedCv['file_path']);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function userData(string $persona, array $payload, string $passwordHash): array
    {
        if ($persona === 'IT_CONSULTANT') {
            [$firstName, $lastName] = $this->splitName((string) ($payload['fullName'] ?? ''));

            return [
                'persona' => $persona,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $payload['email'] ?? '',
                'mobile' => $payload['mobileNumber'] ?? '',
                'password_hash' => $passwordHash,
                'gstin' => $payload['gstin'] ?? null,
                'status' => 'ACTIVE',
                'role' => 'SUBSCRIBER',
            ];
        }

        $companyName = $persona === 'MSP'
            ? (string) ($payload['mspName'] ?? '')
            : (string) ($payload['companyName'] ?? '');

        return [
            'persona' => $persona,
            'first_name' => $companyName,
            'last_name' => null,
            'email' => $payload['contactEmail'] ?? '',
            'mobile' => $payload['contactNumber'] ?? '',
            'password_hash' => $passwordHash,
            'gstin' => $payload['gstin'] ?? null,
            'status' => 'ACTIVE',
            'role' => 'SUBSCRIBER',
        ];
    }

    /** @return array{0: string, 1: string|null} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? $name, $parts[1] ?? null];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function registeredAddressFor(string $persona, array $payload): array
    {
        return $persona === 'IT_CONSULTANT'
            ? ($payload['address'] ?? [])
            : ($payload['registeredAddress'] ?? []);
    }

    /** @param array<string, mixed> $payload */
    private function createCompany(int $userId, string $persona, array $payload): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (
                user_id, company_type, company_name, registered_business_name, registration_number, industry,
                website, contact_email, contact_phone, created_at, updated_at
            ) VALUES (
                :user_id, :company_type, :company_name, :registered_business_name, :registration_number, :industry,
                :website, :contact_email, :contact_phone, NOW(), NOW()
            )'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':company_type' => $persona,
            ':company_name' => $persona === 'MSP' ? ($payload['mspName'] ?? '') : ($payload['companyName'] ?? ''),
            ':registered_business_name' => $persona === 'MSP' ? ($payload['businessName'] ?? null) : null,
            ':registration_number' => $payload['registrationNumber'] ?? null,
            ':industry' => $payload['industry'] ?? null,
            ':website' => $payload['website'] ?? '',
            ':contact_email' => $payload['contactEmail'] ?? '',
            ':contact_phone' => $payload['contactNumber'] ?? '',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $payload @param array<string, mixed> $registeredAddress @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: int|null} */
    private function createBranches(int $companyId, int $userId, array $payload, array $registeredAddress): array
    {
        $addressRepository = new AddressRepository($this->pdo);
        $subscriptionAddress = $registeredAddress;
        $billingAddress = $registeredAddress;
        $selectedBranchId = null;
        $selectedLegacyId = (string) ($payload['subscriptionBranchId'] ?? '');

        foreach (($payload['branches'] ?? []) as $branch) {
            if (!is_array($branch)) {
                continue;
            }
            $addressId = $addressRepository->create($userId, 'BRANCH', $branch);
            $isSelected = $selectedLegacyId !== '' && ($branch['id'] ?? '') === $selectedLegacyId;
            $statement = $this->pdo->prepare(
                'INSERT INTO branches (company_id, branch_name, address_id, is_subscription_branch, created_at, updated_at)
                 VALUES (:company_id, :branch_name, :address_id, :is_subscription_branch, NOW(), NOW())'
            );
            $statement->execute([
                ':company_id' => $companyId,
                ':branch_name' => $branch['branchName'] ?? 'Branch Office',
                ':address_id' => $addressId,
                ':is_subscription_branch' => $isSelected ? 1 : 0,
            ]);

            if ($isSelected) {
                $selectedBranchId = (int) $this->pdo->lastInsertId();
                $subscriptionAddress = $branch;
            }
        }

        if (($payload['billingAddressType'] ?? 'registered_office') === 'selected_branch') {
            $billingAddress = $subscriptionAddress;
        }

        if (($payload['shieldLocationType'] ?? 'registered_office') === 'specific_branch') {
            $addressRepository->create($userId, 'SUBSCRIPTION', $subscriptionAddress);
        }

        $addressRepository->create($userId, 'BILLING', $billingAddress);

        return [$subscriptionAddress, $billingAddress, $selectedBranchId];
    }

    /** @param array<string, mixed> $payload */
    private function createSubscriptionSettings(int $userId, array $payload, ?int $selectedBranchId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO subscription_settings (
                user_id, subscription_location_type, selected_branch_id, billing_address_source, created_at, updated_at
            ) VALUES (
                :user_id, :subscription_location_type, :selected_branch_id, :billing_address_source, NOW(), NOW()
            )'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':subscription_location_type' => ($payload['shieldLocationType'] ?? 'registered_office') === 'specific_branch'
                ? 'SPECIFIC_BRANCH'
                : 'REGISTERED_OFFICE',
            ':selected_branch_id' => $selectedBranchId,
            ':billing_address_source' => ($payload['billingAddressType'] ?? 'registered_office') === 'selected_branch'
                ? 'SELECTED_BRANCH'
                : 'REGISTERED_OFFICE',
        ]);
    }

    /** @param array<string, mixed> $registered @param array<string, mixed> $subscription @param array<string, mixed> $billing */
    private function createGstClassification(int $userId, array $registered, array $subscription, array $billing): void
    {
        $classification = GSTService::classify($registered, $subscription, $billing);
        $statement = $this->pdo->prepare(
            'INSERT INTO gst_classification (user_id, same_country, same_state, tax_type, created_at)
             VALUES (:user_id, :same_country, :same_state, :tax_type, NOW())'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':same_country' => $classification['sameCountry'] ? 1 : 0,
            ':same_state' => $classification['sameState'] ? 1 : 0,
            ':tax_type' => $classification['taxType'],
        ]);
    }

    private function referenceId(string $persona, int $userId): string
    {
        $prefix = match ($persona) {
            'MSP' => 'MSP',
            'COMPANY' => 'COM',
            default => 'CON',
        };

        return sprintf('SHIELD-%s-%s', $prefix, strtoupper(substr(hash('sha256', $persona . $userId . microtime(true)), 0, 7)));
    }
}
