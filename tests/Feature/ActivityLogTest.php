<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_are_logged_automatically(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@sekolah.test',
            'password' => 'password',
        ]);

        // Login
        $this->post('/login', [
            'email' => 'budi@sekolah.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);

        // Logout
        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_admin_can_view_and_filter_activity_logs_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Inventaris']);
        $user = User::factory()->create(['role' => 'user', 'name' => 'Siti']);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'create_item',
            'description' => 'Menambahkan barang Mikroskop Binokuler (LAB-001).',
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'request_loan',
            'description' => 'Siti mengajukan peminjaman Bola Voli (2 unit).',
            'ip_address' => '127.0.0.1',
        ]);

        // Filter action=create_item
        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['action' => 'create_item']))
            ->assertOk()
            ->assertSee('Mikroskop Binokuler')
            ->assertDontSee('Siti mengajukan peminjaman');

        // Non-admin 403
        $this->actingAs($user)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }
}

