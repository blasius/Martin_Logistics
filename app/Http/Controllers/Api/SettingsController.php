<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return AppSetting::orderBy('key')->get(['id', 'key', 'value', 'type', 'description']);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ]);

        $oldFirebaseKeys = ['firebase_api_key', 'firebase_auth_domain', 'firebase_project_id', 'firebase_credentials_json'];

        foreach ($validated['settings'] as $setting) {
            if ($setting['key'] === 'firebase_config') {
                $decoded = json_decode($setting['value'], true);
                if (!$decoded || !is_array($decoded)) {
                    return response()->json(['message' => 'Invalid Firebase configuration format.'], 422);
                }
                $required = ['api_key', 'auth_domain', 'project_id', 'credentials_json'];
                foreach ($required as $field) {
                    if (!isset($decoded[$field]) || trim((string) $decoded[$field]) === '') {
                        return response()->json([
                            'message' => "The {$field} field is required and cannot be empty.",
                        ], 422);
                    }
                }

                // Validate api_key format (Firebase Web API keys start with AIza)
                if (!str_starts_with($decoded['api_key'], 'AIza')) {
                    return response()->json(['message' => 'Web API Key must start with "AIza".'], 422);
                }

                // Validate auth_domain format
                if (!str_contains($decoded['auth_domain'], '.')) {
                    return response()->json(['message' => 'Auth Domain must be a valid domain (e.g. project.firebaseapp.com).'], 422);
                }

                // Validate project_id format
                if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/i', $decoded['project_id'])) {
                    return response()->json(['message' => 'Project ID appears to be invalid.'], 422);
                }

                // Validate credentials_json is valid JSON with required service account fields
                $testCreds = json_decode($decoded['credentials_json'], true);
                if (!is_array($testCreds)) {
                    return response()->json(['message' => 'Service Account Credentials must be valid JSON.'], 422);
                }
                $requiredCredFields = ['type', 'project_id', 'private_key', 'client_email'];
                foreach ($requiredCredFields as $field) {
                    if (!isset($testCreds[$field]) || trim((string) $testCreds[$field]) === '') {
                        return response()->json([
                            'message' => "Service Account Credentials is missing required field: {$field}.",
                        ], 422);
                    }
                }
                if ($testCreds['type'] !== 'service_account') {
                    return response()->json(['message' => 'Service Account Credentials must have type "service_account".'], 422);
                }

                // Write credentials to dynamic.json for the Firebase SDK
                $path = storage_path('app/firebase/dynamic.json');
                $creds = $decoded['credentials_json'];
                if ($creds) {
                    $dir = dirname($path);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($path, $creds);
                } elseif (file_exists($path)) {
                    unlink($path);
                }

                AppSetting::setValue('firebase_config', $setting['value'], 'json', 'Firebase configuration (API key, Auth domain, Project ID, Service account JSON)');
            } elseif (in_array($setting['key'], $oldFirebaseKeys)) {
                // Reject old individual firebase keys — must use consolidated firebase_config
                return response()->json([
                    'message' => 'Firebase settings must be saved through the consolidated configuration.',
                ], 422);
            } else {
                AppSetting::setValue($setting['key'], $setting['value'] ?? '');
            }
        }

        return response()->json(['message' => 'Settings updated.']);
    }

    public function firebaseConfig()
    {
        $config = AppSetting::getValue('firebase_config', []);

        return response()->json([
            'apiKey' => $config['api_key'] ?? '',
            'authDomain' => $config['auth_domain'] ?? '',
            'projectId' => $config['project_id'] ?? '',
            'configured' => !empty($config['api_key']),
        ]);
    }
}
