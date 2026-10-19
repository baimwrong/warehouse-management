<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => "{$role}@wms.internal",
            'password' => Hash::make('secret123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_rate_limiting_triggers_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong@wms.internal',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(302);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'wrong@wms.internal',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(429);
    }

    public function test_admin_can_access_admin_import_and_reports(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get('/admin/import')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/reports/valuation')
            ->assertStatus(200);
    }

    public function test_manager_cannot_access_admin_import_but_can_access_reports(): void
    {
        $manager = $this->createUser('manager');

        $this->actingAs($manager)
            ->get('/admin/import')
            ->assertStatus(403);

        $this->actingAs($manager)
            ->get('/reports/valuation')
            ->assertStatus(200);
    }

    public function test_operator_cannot_access_reports_or_admin_import(): void
    {
        $operator = $this->createUser('operator');

        $this->actingAs($operator)
            ->get('/admin/import')
            ->assertStatus(403);

        $this->actingAs($operator)
            ->get('/reports/valuation')
            ->assertStatus(403);

        $this->actingAs($operator)
            ->get('/inbound/create')
            ->assertStatus(200);

        $this->actingAs($operator)
            ->get('/outbound/create')
            ->assertStatus(403);
    }

    public function test_dispatcher_cannot_access_inbound_create_but_can_access_outbound(): void
    {
        $dispatcher = $this->createUser('dispatcher');

        $this->actingAs($dispatcher)
            ->get('/admin/import')
            ->assertStatus(403);

        $this->actingAs($dispatcher)
            ->get('/inbound/create')
            ->assertStatus(403);

        $this->actingAs($dispatcher)
            ->get('/outbound/create')
            ->assertStatus(200);
    }
}
