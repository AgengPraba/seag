<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\ExternalAccessRequest;
use App\Models\SimrsUserMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private string $serviceSecret = 'super-secret-ci3-key-2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.internal_api_service_secret' => $this->serviceSecret]);
        putenv("INTERNAL_API_SERVICE_SECRET={$this->serviceSecret}");
        $_ENV['INTERNAL_API_SERVICE_SECRET'] = $this->serviceSecret;
    }

    public function test_rejects_unauthorized_service_client(): void
    {
        $response = $this->postJson('/internal/v1/access/verify', [
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'test-sub-123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'allowed' => false,
                'status' => 'unauthorized',
            ]);
    }

    public function test_rejects_unmapped_identity(): void
    {
        $response = $this->postJson('/internal/v1/access/verify', [
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'unknown-subject',
        ], [
            'X-Gateway-Service-Secret' => $this->serviceSecret,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'allowed' => false,
                'status' => 'not_mapped',
            ]);
    }

    public function test_rejects_pending_or_unapproved_access_request(): void
    {
        $admin = Administrator::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => 'access_admin',
            'status' => 'active',
        ]);

        $req = ExternalAccessRequest::create([
            'employee_id' => 'PEG-001',
            'employee_name' => 'Dokter Sp.A',
            'status' => 'pending',
            'requested_by' => $admin->id,
        ]);

        SimrsUserMapping::create([
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'keycloak-sub-456',
            'simrs_user_id' => '1024',
            'access_request_id' => $req->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/internal/v1/access/verify', [
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'keycloak-sub-456',
        ], [
            'X-Gateway-Service-Secret' => $this->serviceSecret,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'allowed' => false,
                'status' => 'pending',
            ]);
    }

    public function test_grants_access_for_approved_mapping(): void
    {
        $admin = Administrator::create([
            'name' => 'Admin Test',
            'email' => 'admin2@test.local',
            'password' => Hash::make('password'),
            'role' => 'access_admin',
            'status' => 'active',
        ]);

        $req = ExternalAccessRequest::create([
            'employee_id' => 'PEG-002',
            'employee_name' => 'Dokter Sp.PD',
            'status' => 'approved',
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);

        SimrsUserMapping::create([
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'keycloak-sub-789',
            'simrs_user_id' => '2048',
            'access_request_id' => $req->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/internal/v1/access/verify', [
            'issuer' => 'https://sso.rsud-tidar.id/realms/rsud',
            'subject' => 'keycloak-sub-789',
        ], [
            'X-Gateway-Service-Secret' => $this->serviceSecret,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'allowed' => true,
                'status' => 'approved',
                'simrs_user_id' => '2048',
            ]);
    }
}
