<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

class SchoolProfile
{
    public function values(): array
    {
        $defaults = [
            'school' => 'Fiat Lux Academe',
            'district' => '',
            'school_id' => '',
            'division' => '',
            'region' => '',
        ];

        try {
            $settings = DB::connection('reghub')
                ->table('settings')
                ->whereIn('key', [
                    'institution_name',
                    'school_name',
                    'school_district',
                    'school_id',
                    'school_division',
                    'school_region',
                ])
                ->pluck('value', 'key');
        } catch (Throwable) {
            return $defaults;
        }

        return [
            'school' => $settings->get('school_name') ?: ($settings->get('institution_name') ?: $defaults['school']),
            'district' => $settings->get('school_district') ?: '',
            'school_id' => $settings->get('school_id') ?: '',
            'division' => $settings->get('school_division') ?: '',
            'region' => $settings->get('school_region') ?: '',
        ];
    }
}
