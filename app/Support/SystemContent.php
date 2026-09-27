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

        return (string) (Setting::where('key', $key)->value('value') ?: $default);
    }

    public static function schoolName(): string
    {
        return self::get('school_name', self::get('institution_name', 'Fiat Lux Academe'));
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
