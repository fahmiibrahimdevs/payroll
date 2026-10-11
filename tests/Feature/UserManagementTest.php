<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Administrator Role',
        ]);
        Role::firstOrCreate(['name' => 'user'], [
            'display_name' => 'User',
            'description' => 'Standard User Role',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $response = $this->actingAs($user)->get(route('admin.users'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $response = $this->actingAs($admin->fresh())->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('Daftar Pengguna');
    }

    public function test_admin_can_trigger_confirm_toggle_status(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $targetUser = User::factory()->create(['is_active' => true]);
        $targetUser->addRole('user');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('confirmToggleStatus', $targetUser->id)
            ->assertDispatched('swal:confirm');
    }

    public function test_admin_cannot_trigger_confirm_toggle_status_for_self(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->addRole('admin');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('confirmToggleStatus', $admin->id)
            ->assertDispatched('swal:alert');
    }

    public function test_admin_can_toggle_user_status_and_logs_activity(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $targetUser = User::factory()->create([
            'is_active' => true,
        ]);
        $targetUser->addRole('user');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('toggleStatus', $targetUser->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'users',
            'action' => 'USER_DEACTIVATED',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
        ]);
        $admin->addRole('admin');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('toggleStatus', $admin->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_trigger_confirm_delete(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->addRole('user');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('confirmDelete', $targetUser->id)
            ->assertDispatched('swal:confirm-delete');
    }

    public function test_admin_cannot_trigger_confirm_delete_for_self(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('confirmDelete', $admin->id)
            ->assertDispatched('swal:alert');
    }

    public function test_admin_can_delete_user_and_logs_activity(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->addRole('user');
        $targetUserId = $targetUser->id;

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('deleteUser', $targetUserId)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('users', [
            'id' => $targetUserId,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'users',
            'action' => 'USER_DELETED',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $this->actingAs($admin->fresh());

        Volt::test('pages.admin.user-index')
            ->call('deleteUser', $admin->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }
}
