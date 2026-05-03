<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_whatsapp_otp_for_valid_contact()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'type' => 'whatsapp',
            'value' => '+1234567890',
        ]);

        $response = $this->postJson('/api/mobile/auth/request-whatsapp-otp', [
            'identifier' => '+1234567890',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Verification code sent (mocked).']);

        $this->assertNotNull($contact->fresh()->verification_code);
    }

    public function test_can_verify_whatsapp_otp_and_login()
    {
        $user = User::factory()->create();
        $code = '123456';
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'type' => 'whatsapp',
            'value' => '+1234567890',
            'verification_code' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(10),
            'verified_at' => null,
        ]);

        $response = $this->postJson('/api/mobile/auth/verify-whatsapp-otp', [
            'identifier' => '+1234567890',
            'code' => $code,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);

        $this->assertNotNull($contact->fresh()->verified_at);
        $this->assertNull($contact->fresh()->verification_code);
    }

    public function test_can_bypass_firebase_phone_verification_in_dev()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'type' => 'phone',
            'value' => '+1234567890',
            'verified_at' => null,
        ]);

        $response = $this->postJson('/api/mobile/auth/verify-firebase-phone', [
            'identifier' => '+1234567890',
            'idToken' => 'TEST_BYPASS_TOKEN_123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);

        $this->assertNotNull($contact->fresh()->verified_at);
    }
}
