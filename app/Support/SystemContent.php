<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemContent
{
    public static function get(string $key, string $default = ''): string
    {
        // Standalone template previews and first-install pages may run before migrations.
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        $value = Setting::where('key', $key)->value('value');

        return $value === null ? $default : (string) $value;
    }

    public static function schoolName(): string
    {
        return self::get('school_name', self::get('institution_name', 'Fiat Lux Academe'));
    }

    public static function enabled(string $key, bool $default = true): bool
    {
        return self::get($key, $default ? '1' : '0') === '1';
    }

    public static function issuer(): string
    {
        return self::get('document_issuer', (string) config('document_verification.issuer', config('app.name')));
    }

    public static function logoPath(?string $default = null): string
    {
        $path = self::get('school_logo_path');
        if (str_starts_with($path, 'school-branding/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        return $default ?? public_path('images/fiat.png');
    }

    public static function logoUrl(): string
    {
        return self::get('school_logo_path') ? route('school.logo') : asset('images/fiat.png');
    }
}
