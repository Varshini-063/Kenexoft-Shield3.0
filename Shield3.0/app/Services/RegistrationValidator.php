<?php
declare(strict_types=1);

namespace App\Services;

final class RegistrationValidator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $payload @param array<string, mixed>|null $cvFile @return array<string, string> */
    public function validate(string $persona, array $payload, ?array $cvFile = null): array
    {
        $this->errors = [];
        $persona = self::normalisePersona($persona);

        if (!in_array($persona, ['MSP', 'COMPANY', 'IT_CONSULTANT'], true)) {
            return ['persona' => 'Please select MSP, Company, or IT Consultant.'];
        }

        if ($persona === 'MSP') {
            $this->validateMsp($payload);
        } elseif ($persona === 'COMPANY') {
            $this->validateCompany($payload);
        } else {
            $this->validateConsultant($payload, $cvFile);
        }

        return $this->errors;
    }

    public static function normalisePersona(string $persona): string
    {
        $persona = strtoupper(trim($persona));

        return match ($persona) {
            'MSP' => 'MSP',
            'COMPANY' => 'COMPANY',
            'CONSULTANT', 'IT_CONSULTANT' => 'IT_CONSULTANT',
            default => $persona,
        };
    }

    /** @param array<string, mixed> $payload */
    private function validateMsp(array $payload): void
    {
        $this->required($payload, 'mspName', 'MSP name is required');
        $this->required($payload, 'businessName', 'Registered business name is required');
        $this->url($payload, 'website', 'Invalid website URL', false);
        $this->email($payload, 'contactEmail');
        $this->phone($payload, 'contactNumber');
        $this->gstin($payload, 'gstin');
        $this->address($payload, 'registeredAddress');
        $this->branches($payload);
        $this->branchSelection($payload);
        $this->requiredArray($payload, 'services', 'Please select at least one service');
        $this->customWhenOther($payload, 'services', 'customServices', 'Please specify the custom service');
        $this->security($payload);
    }

    /** @param array<string, mixed> $payload */
    private function validateCompany(array $payload): void
    {
        $this->required($payload, 'companyName', 'Company Name is required');
        $this->required($payload, 'registrationNumber', 'Registration Number is required');
        $this->required($payload, 'industry', 'Industry is required');
        $this->url($payload, 'website', 'Invalid website URL', false);
        $this->email($payload, 'contactEmail');
        $this->phone($payload, 'contactNumber');
        $this->gstin($payload, 'gstin');
        $this->address($payload, 'registeredAddress');
        $this->branches($payload);
        $this->branchSelection($payload);
        $this->security($payload);
    }

    /** @param array<string, mixed> $payload @param array<string, mixed>|null $cvFile */
    private function validateConsultant(array $payload, ?array $cvFile): void
    {
        $this->required($payload, 'fullName', 'Full name is required');
        $this->email($payload, 'email');
        $this->phone($payload, 'mobileNumber');
        $this->url($payload, 'website', 'Invalid portfolio URL', true);
        $this->url($payload, 'linkedIn', 'Invalid LinkedIn URL', true);
        $this->address($payload, 'address');
        $this->requiredArray($payload, 'expertise', 'Please select at least one area of expertise');
        $this->customWhenOther($payload, 'expertise', 'customExpertise', 'Please specify your custom expertise');
        $this->gstin($payload, 'gstin');
        $this->security($payload);
        $this->errors = array_merge($this->errors, (new CVUploadService())->validateCV($cvFile));
    }

    /** @param array<string, mixed> $data */
    private function required(array $data, string $path, string $message): bool
    {
        $value = $this->value($data, $path);
        if (trim((string) $value) === '') {
            $this->errors[$path] = $message;
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $data */
    private function email(array $data, string $path): void
    {
        if (!$this->required($data, $path, 'Email address is required')) {
            return;
        }

        $value = trim((string) $this->value($data, $path));
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$path] = 'Invalid email address';
        }
    }

    /** @param array<string, mixed> $data */
    private function phone(array $data, string $path): void
    {
        if (!$this->required($data, $path, 'Phone number is required')) {
            return;
        }

        $value = trim((string) $this->value($data, $path));
        if (!preg_match('/^\+?[0-9\s-]{8,15}$/', $value)) {
            $this->errors[$path] = 'Invalid phone number format';
        }
    }

    /** @param array<string, mixed> $data */
    private function url(array $data, string $path, string $message, bool $optional): void
    {
        $value = trim((string) $this->value($data, $path));
        if ($value === '') {
            if (!$optional) {
                $this->errors[$path] = 'Website is required';
            }
            return;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$path] = $message;
        }
    }

    /** @param array<string, mixed> $data */
    private function address(array $data, string $prefix): void
    {
        $this->required($data, $prefix . '.addressLine1', 'Address Line 1 is required');
        $this->required($data, $prefix . '.city', 'City is required');
        $this->required($data, $prefix . '.state', 'State / Province is required');
        $this->required($data, $prefix . '.country', 'Country is required');
        $this->required($data, $prefix . '.postalCode', 'Postal / ZIP Code is required');
    }

    /** @param array<string, mixed> $data */
    private function branches(array $data): void
    {
        $branches = $data['branches'] ?? [];
        if (!is_array($branches)) {
            $this->errors['branches'] = 'Branches must be a list.';
            return;
        }

        foreach ($branches as $index => $branch) {
            if (!is_array($branch)) {
                $this->errors["branches.$index"] = 'Invalid branch record.';
                continue;
            }

            foreach ([
                'branchName' => 'Branch Name is required',
                'addressLine1' => 'Address Line 1 is required',
                'city' => 'City is required',
                'state' => 'State is required',
                'country' => 'Country is required',
                'postalCode' => 'Postal Code is required',
            ] as $field => $message) {
                if (trim((string) ($branch[$field] ?? '')) === '') {
                    $this->errors["branches.$index.$field"] = $message;
                }
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function branchSelection(array $data): void
    {
        $location = (string) ($data['shieldLocationType'] ?? 'registered_office');
        $billing = (string) ($data['billingAddressType'] ?? 'registered_office');

        if (!in_array($location, ['registered_office', 'specific_branch'], true)) {
            $this->errors['shieldLocationType'] = 'Please choose a valid SHIELD subscription location.';
        }

        if (!in_array($billing, ['registered_office', 'selected_branch'], true)) {
            $this->errors['billingAddressType'] = 'Please choose a valid billing address.';
        }

        if ($location === 'specific_branch' && trim((string) ($data['subscriptionBranchId'] ?? '')) === '') {
            $this->errors['subscriptionBranchId'] = 'Please select the subscription branch office';
            return;
        }

        if ($location === 'specific_branch') {
            $selectedBranchId = (string) ($data['subscriptionBranchId'] ?? '');
            $branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
            $exists = false;
            foreach ($branches as $branch) {
                if (is_array($branch) && (string) ($branch['id'] ?? '') === $selectedBranchId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->errors['subscriptionBranchId'] = 'Selected subscription branch does not exist.';
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function requiredArray(array $data, string $path, string $message): void
    {
        $value = $data[$path] ?? [];
        if (!is_array($value) || count($value) === 0) {
            $this->errors[$path] = $message;
        }
    }

    /** @param array<string, mixed> $data */
    private function customWhenOther(array $data, string $arrayPath, string $customPath, string $message): void
    {
        $values = $data[$arrayPath] ?? [];
        if (is_array($values) && in_array('Others', $values, true) && trim((string) ($data[$customPath] ?? '')) === '') {
            $this->errors[$customPath] = $message;
        }
    }

    /** @param array<string, mixed> $data */
    private function gstin(array $data, string $path): void
    {
        $value = strtoupper(trim((string) $this->value($data, $path)));
        if ($value !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $value)) {
            $this->errors[$path] = 'Invalid Indian GSTIN format (e.g. 22AAAAA1111A1Z5)';
        }
    }

    /** @param array<string, mixed> $data */
    private function security(array $data): void
    {
        $password = (string) $this->value($data, 'security.password');
        $confirm = (string) $this->value($data, 'security.confirmPassword');

        if (strlen($password) < 8) {
            $this->errors['security.password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $this->errors['security.password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $this->errors['security.password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $this->errors['security.password'] = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->errors['security.password'] = 'Password must contain at least one special character';
        }

        if ($confirm === '') {
            $this->errors['security.confirmPassword'] = 'Please confirm your password';
        } elseif ($password !== $confirm) {
            $this->errors['security.confirmPassword'] = 'Passwords do not match';
        }
    }

    /** @param array<string, mixed> $data */
    private function value(array $data, string $path): mixed
    {
        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
