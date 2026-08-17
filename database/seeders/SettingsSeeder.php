<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::setValue('google_analytics_id', '', 'string', 'Google Analytics Measurement ID (e.g. G-XXXXXXXXXX)');

        AppSetting::setValue('firebase_config', [
            'api_key' => '',
            'auth_domain' => '',
            'project_id' => '',
            'credentials_json' => '',
        ], 'json', 'Firebase configuration (API key, Auth domain, Project ID, Service account JSON)');
    }
}
