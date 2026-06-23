<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldKeys = ['firebase_api_key', 'firebase_auth_domain', 'firebase_project_id', 'firebase_credentials_json'];
        $existing = DB::table('app_settings')->whereIn('key', $oldKeys)->get()->keyBy('key');

        $config = [
            'api_key' => $existing->get('firebase_api_key')?->value ?? '',
            'auth_domain' => $existing->get('firebase_auth_domain')?->value ?? '',
            'project_id' => $existing->get('firebase_project_id')?->value ?? '',
            'credentials_json' => $existing->get('firebase_credentials_json')?->value ?? '',
        ];

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'firebase_config'],
            [
                'value' => json_encode($config),
                'type' => 'json',
                'description' => 'Firebase configuration (API key, Auth domain, Project ID, Service account JSON)',
            ]
        );

        DB::table('app_settings')->whereIn('key', $oldKeys)->delete();
    }

    public function down(): void
    {
        $row = DB::table('app_settings')->where('key', 'firebase_config')->first();
        if ($row) {
            $config = json_decode($row->value, true) ?? [];
            $old = [
                ['key' => 'firebase_api_key', 'value' => $config['api_key'] ?? '', 'type' => 'string', 'description' => 'Firebase Web API Key'],
                ['key' => 'firebase_auth_domain', 'value' => $config['auth_domain'] ?? '', 'type' => 'string', 'description' => 'Firebase Auth Domain (e.g. project.firebaseapp.com)'],
                ['key' => 'firebase_project_id', 'value' => $config['project_id'] ?? '', 'type' => 'string', 'description' => 'Firebase Project ID'],
                ['key' => 'firebase_credentials_json', 'value' => $config['credentials_json'] ?? '', 'type' => 'json', 'description' => 'Firebase Service Account JSON (server-side)'],
            ];
            foreach ($old as $row) {
                DB::table('app_settings')->updateOrInsert(
                    ['key' => $row['key']],
                    $row
                );
            }
            DB::table('app_settings')->where('key', 'firebase_config')->delete();
        }
    }
};
