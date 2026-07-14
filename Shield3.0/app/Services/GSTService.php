<?php
declare(strict_types=1);

namespace App\Services;

final class GSTService
{
    /** @param array<string, mixed>|null $registered @param array<string, mixed>|null $subscription @param array<string, mixed>|null $billing */
    public static function classify(?array $registered, ?array $subscription, ?array $billing): array
    {
        $subscriptionAddress = $subscription ?: $registered;
        $registeredCountry = self::normalise($registered['country'] ?? '');
        $registeredState = self::normalise($registered['state'] ?? '');
        $subscriptionCountry = self::normalise($subscriptionAddress['country'] ?? '');
        $subscriptionState = self::normalise($subscriptionAddress['state'] ?? '');
        $billingCountry = self::normalise($billing['country'] ?? '');
        $billingState = self::normalise($billing['state'] ?? '');

        $sameCountry = $registeredCountry !== ''
            && $registeredCountry === $subscriptionCountry
            && $registeredCountry === $billingCountry;
        $sameState = $sameCountry
            && $registeredState !== ''
            && $registeredState === $subscriptionState
            && $registeredState === $billingState;

        return [
            'sameCountry' => $sameCountry,
            'sameState' => $sameState,
            'taxType' => $sameCountry && $sameState ? 'CGST_SGST' : 'IGST',
        ];
    }

    /** @param array<string, mixed>|null $registered @param array<string, mixed>|null $subscription @param array<string, mixed>|null $billing */
    public static function calculate(?array $registered, ?array $subscription, ?array $billing): array
    {
        $classification = self::classify($registered, $subscription, $billing);

        if (!$classification['sameCountry']) {
            return [
                ...$classification,
                'displayTaxType' => 'IGST',
                'category' => 'Different Country',
                'details' => 'IGST applied due to cross-country addresses.',
            ];
        }

        if ($classification['sameState']) {
            return [
                ...$classification,
                'displayTaxType' => 'CGST + SGST',
                'category' => 'Same State',
                'details' => sprintf(
                    'CGST + SGST applied: Supplier, Subscriber, and Billing addresses are in the same state (%s).',
                    $registered['state'] ?? 'N/A'
                ),
            ];
        }

        $subscriptionAddress = $subscription ?: $registered;

        return [
            ...$classification,
            'displayTaxType' => 'IGST',
            'category' => 'Different State',
            'details' => sprintf(
                'IGST applied: Inter-state transactions between %s (Registered), %s (Subscription), and %s (Billing).',
                $registered['state'] ?? 'N/A',
                $subscriptionAddress['state'] ?? 'N/A',
                $billing['state'] ?? 'N/A'
            ),
        ];
    }

    private static function normalise(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }
}
