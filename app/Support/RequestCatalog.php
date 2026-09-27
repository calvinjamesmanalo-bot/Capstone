<?php

namespace App\Support;

use App\Models\Setting;

class RequestCatalog
{
    public const BUILT_INS = [
        'Form 137' => ['price_form_137', 150],
        'Form 138' => ['price_form_138', 100],
        'Certificate of Enrollment' => ['price_certificate_enrollment', 100],
        'Certificate of Completion' => ['price_certificate_completion', 120],
        'Certificate of Good Moral Character' => ['price_good_moral', 100],
        'Certificate of Recognition' => ['price_certificate_recognition', 120],
        'Diploma' => ['price_diploma', 150],
    ];

    public static function prices(): array
    {
        $settings = Setting::whereIn('key', array_column(self::BUILT_INS, 0))->pluck('value', 'key');

        return collect(self::BUILT_INS)->map(fn ($entry) => (float) ($settings[$entry[0]] ?? $entry[1]))->all();
    }

    public static function builtIns(): array
    {
        $prices = self::prices();
        $availability = Setting::whereIn('key', array_map(
            fn ($entry) => self::availabilityKey($entry[0]), self::BUILT_INS
        ))->pluck('value', 'key');

        return collect(self::BUILT_INS)->map(fn ($entry, $name) => [
            'key' => $entry[0],
            'fee' => $prices[$name],
            'enabled' => ($availability[self::availabilityKey($entry[0])] ?? '1') !== '0',
        ])->all();
    }

    public static function availablePrices(): array
    {
        return collect(self::builtIns())->filter(fn ($entry) => $entry['enabled'])
            ->map(fn ($entry) => $entry['fee'])->all();
    }

    public static function availabilityKey(string $priceKey): string
    {
        return 'request_enabled_'.substr($priceKey, strlen('price_'));
    }

    public static function builtInName(string $priceKey): ?string
    {
        foreach (self::BUILT_INS as $name => $entry) {
            if ($entry[0] === $priceKey) {
                return $name;
            }
        }

        return null;
    }
}
