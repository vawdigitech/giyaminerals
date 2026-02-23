<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'overtime_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'overtime',
                'description' => 'Enable or disable overtime calculation',
            ],
            [
                'key' => 'overtime_threshold_hours',
                'value' => '2.5',
                'type' => 'decimal',
                'group' => 'overtime',
                'description' => 'Hours above standard working hours to qualify for overtime',
            ],
            [
                'key' => 'overtime_bonus_percentage',
                'value' => '50',
                'type' => 'integer',
                'group' => 'overtime',
                'description' => 'Overtime bonus as percentage of daily rate',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
