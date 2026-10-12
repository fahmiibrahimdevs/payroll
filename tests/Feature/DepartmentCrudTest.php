<?php

namespace Tests\Feature;

use App\Livewire\Departments\Index;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('departments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_departments_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('Kelola Department');
    }

    public function test_user_without_admin_role_cannot_view_departments_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('departments.index'));

        $response->assertForbidden();
    }

    public function test_can_create_department_and_logs_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.name', 'Human Resources')
            ->set('form.description', 'Mengelola manajemen SDM dan kepegawaian.')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'departmentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('departments', [
            'name' => 'Human Resources',
            'description' => 'Mengelola manajemen SDM dan kepegawaian.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'departments',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_can_update_department_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $department = Department::factory()->create([
            'name' => 'Finance & Accounting',
            'description' => 'Departemen keuangan lama.',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $department->id)
            ->assertSet('form.name', 'Finance & Accounting')
            ->set('form.name', 'Finance, Accounting & Tax')
            ->set('form.description', 'Departemen keuangan dan perpajakan baru.')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'departmentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Finance, Accounting & Tax',
            'description' => 'Departemen keuangan dan perpajakan baru.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'departments',
            'action' => 'UPDATE',
            'level' => 'info',
        ]);
    }

    public function test_can_delete_department_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $department = Department::factory()->create([
            'name' => 'Research & Development',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $department->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('delete', $department->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'departments',
            'action' => 'DELETE',
            'level' => 'info',
        ]);
    }

    public function test_search_filter_returns_matching_departments(): void
    {
        $user = $this->createAdminUser();
        Department::factory()->create(['name' => 'Information Technology']);
        Department::factory()->create(['name' => 'Supply Chain']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Information')
            ->assertSee('Information Technology')
            ->assertDontSee('Supply Chain');
    }

    public function test_department_name_must_be_unique(): void
    {
        $user = $this->createAdminUser();
        Department::factory()->create(['name' => 'Marketing']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Marketing')
            ->call('store')
            ->assertHasErrors(['form.name' => 'unique']);
    }

    public function test_director_can_view_and_crud_departments(): void
    {
        $director = $this->createDirectorUser();

        $response = $this->actingAs($director)->get(route('departments.index'));
        $response->assertOk();

        $this->actingAs($director);

        // Director can create department
        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Divisi Eksekutif')
            ->set('form.description', 'Departemen khusus jajaran direksi.')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'departmentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('departments', [
            'name' => 'Divisi Eksekutif',
        ]);

        $department = Department::where('name', 'Divisi Eksekutif')->first();

        // Director can update department
        Livewire::test(Index::class)
            ->call('edit', $department->id)
            ->set('form.name', 'Divisi Eksekutif & Legal')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'departmentModal');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Divisi Eksekutif & Legal',
        ]);

        // Director can delete department
        Livewire::test(Index::class)
            ->call('delete', $department->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    }
}
