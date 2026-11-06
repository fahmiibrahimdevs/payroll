<?php

namespace Tests\Feature;

use App\Livewire\WorkLocations\Index;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WorkLocationCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('work-locations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_view_work_locations_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('work-locations.index'));

        $response->assertOk();
        $response->assertSee('Lokasi Kerja');
    }

    public function test_non_admin_cannot_view_work_locations_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('work-locations.index'));

        $response->assertForbidden();
    }

    public function test_can_create_work_location(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.name', 'Site Proyek Cikarang')
            ->set('form.address', 'Jl. Industri Jababeka, Cikarang')
            ->set('form.latitude', '-6.30250000')
            ->set('form.longitude', '107.15500000')
            ->set('form.radius_meters', 250)
            ->set('form.is_active', true)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'workLocationModal');

        $this->assertDatabaseHas('work_locations', [
            'name' => 'Site Proyek Cikarang',
            'radius_meters' => 250,
            'is_active' => 1,
        ]);
    }

    public function test_can_update_work_location(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $location = WorkLocation::create([
            'name' => 'Gudang Lama',
            'address' => 'Jl. Lama No. 1',
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        Livewire::test(Index::class)
            ->call('edit', $location->id)
            ->assertDispatched('open-modal')
            ->set('form.name', 'Gudang Baru Sentul')
            ->set('form.radius_meters', 150)
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'workLocationModal');

        $this->assertDatabaseHas('work_locations', [
            'id' => $location->id,
            'name' => 'Gudang Baru Sentul',
            'radius_meters' => 150,
        ]);
    }

    public function test_can_toggle_work_location_status(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $location = WorkLocation::create([
            'name' => 'Site Dinamis',
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        Livewire::test(Index::class)
            ->call('toggleStatus', $location->id);

        $this->assertDatabaseHas('work_locations', [
            'id' => $location->id,
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_assign_employee_to_location_with_date_range(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $employee = \App\Models\Employee::factory()->create();
        $location = WorkLocation::create([
            'name' => 'Site Lapangan Subang',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        Livewire::test(Index::class)
            ->call('openAssignmentModal', $location->id)
            ->assertDispatched('open-modal', id: 'locationAssignmentModal')
            ->set('assignmentForm.employee_id', $employee->id)
            ->set('assignmentForm.work_location_id', $location->id)
            ->set('assignmentForm.start_date', '2026-11-06')
            ->set('assignmentForm.end_date', '2026-11-08')
            ->set('assignmentForm.notes', 'Penugasan 3 hari instalasi site')
            ->call('storeAssignment')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'locationAssignmentModal');

        $this->assertDatabaseHas('employee_location_assignments', [
            'employee_id' => $employee->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-08',
            'notes' => 'Penugasan 3 hari instalasi site',
        ]);
    }

    public function test_admin_can_delete_employee_location_assignment(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $employee = \App\Models\Employee::factory()->create();
        $location = WorkLocation::create([
            'name' => 'Site Cisalak',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $assignment = \App\Models\EmployeeLocationAssignment::create([
            'employee_id' => $employee->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-08',
            'notes' => 'Tugas sementara',
        ]);

        Livewire::test(Index::class)
            ->call('deleteAssignment', $assignment->id);

        $this->assertDatabaseMissing('employee_location_assignments', [
            'id' => $assignment->id,
        ]);
    }
}
