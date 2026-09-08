<?php

namespace App\Support;

use App\Models\Setting;

class SchoolProfile
{
    public function values(): array
    {
        $settings = Setting::whereIn('key', ['institution_name', 'school_name', 'school_district', 'school_id', 'school_division', 'school_region'])->pluck('value', 'key');

        return [
            'school' => $settings->get('school_name') ?: ($settings->get('institution_name') ?: 'Fiat Lux Academe'),
            'district' => $settings->get('school_district') ?: '',
            'school_id' => $settings->get('school_id') ?: '',
            'division' => $settings->get('school_division') ?: '',
            'region' => $settings->get('school_region') ?: '',
        ];
    }
}
