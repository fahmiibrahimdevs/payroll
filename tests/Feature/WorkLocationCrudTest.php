<?php

namespace Tests\Feature;

use App\Livewire\WorkLocations\Index;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLocationAssignment;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\WorkLocationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
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

        $employee = Employee::factory()->create();
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

    public function test_selecting_an_employee_returns_all_default_locations_for_the_assignment_picker(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $primaryLocation = WorkLocation::create([
            'name' => 'Lokasi Default Utama',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $secondaryLocation = WorkLocation::create([
            'name' => 'Lokasi Default Kedua',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $employee = Employee::factory()->active()->create(['work_location_id' => $primaryLocation->id]);
        $employee->defaultWorkLocations()->attach($secondaryLocation->id);

        Livewire::test(Index::class)
            ->assertSeeHtml('data-default-location-ids="'.$primaryLocation->id.','.$secondaryLocation->id.'"')
            ->call('selectAssignmentEmployee', $employee->id)
            ->assertSet('assignmentForm.employee_id', (string) $employee->id)
            ->assertReturned([
                'employeeId' => (string) $employee->id,
                'locationIds' => [$primaryLocation->id, $secondaryLocation->id],
            ]);
    }

    public function test_site_assignment_rejects_a_default_location_selected_in_livewire(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $primaryLocation = WorkLocation::create([
            'name' => 'Lokasi Utama Karyawan',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $secondaryLocation = WorkLocation::create([
            'name' => 'Lokasi Kedua Karyawan',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $employee = Employee::factory()->active()->create(['work_location_id' => $primaryLocation->id]);
        $employee->defaultWorkLocations()->attach($secondaryLocation->id);

        $component = Livewire::test(Index::class)
            ->set('assignmentForm.employee_id', $employee->id)
            ->set('assignmentForm.work_location_id', $primaryLocation->id)
            ->set('assignmentForm.start_date', '2026-11-06')
            ->set('assignmentForm.end_date', '2026-11-08')
            ->call('storeAssignment')
            ->assertHasErrors(['assignmentForm.work_location_id'])
            ->assertSee('Lokasi default karyawan tidak dapat dipilih sebagai lokasi penugasan sementara.');

        $component
            ->set('assignmentForm.work_location_id', $secondaryLocation->id)
            ->call('storeAssignment')
            ->assertHasErrors(['assignmentForm.work_location_id']);

        $this->assertDatabaseCount('employee_location_assignments', 0);
    }

    public function test_work_location_service_rejects_assigning_an_employee_to_a_default_location(): void
    {
        $user = $this->createAdminUser();
        $primaryLocation = WorkLocation::create([
            'name' => 'Lokasi Default Primer',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $secondaryLocation = WorkLocation::create([
            'name' => 'Lokasi Default Sekunder',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $employee = Employee::factory()->active()->create(['work_location_id' => $primaryLocation->id]);
        $employee->defaultWorkLocations()->attach($secondaryLocation->id);
        $service = app(WorkLocationService::class);

        try {
            $service->assignEmployee([
                'employee_id' => $employee->id,
                'work_location_id' => $secondaryLocation->id,
                'start_date' => '2026-11-06',
                'end_date' => '2026-11-08',
                'notes' => null,
            ], $user);

            $this->fail('Expected the service to reject an employee default location.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('assignmentForm.work_location_id', $exception->errors());
        }

        $this->assertDatabaseCount('employee_location_assignments', 0);
    }

    public function test_admin_can_delete_employee_location_assignment(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $employee = Employee::factory()->create();
        $location = WorkLocation::create([
            'name' => 'Site Cisalak',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $assignment = EmployeeLocationAssignment::create([
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

    public function test_admin_can_paginate_site_assignments_independently_from_work_locations(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $location = WorkLocation::create([
            'name' => 'Site Pagination',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        Employee::factory()->active()->count(12)->create()->each(function (Employee $employee, int $index) use ($location): void {
            $employee->update(['full_name' => 'Paginated Employee '.($index + 1)]);

            EmployeeLocationAssignment::create([
                'employee_id' => $employee->id,
                'work_location_id' => $location->id,
                'start_date' => now()->addDays($index)->toDateString(),
                'end_date' => now()->addDays($index + 2)->toDateString(),
                'notes' => 'Paginated Note '.($index + 1),
            ]);
        });

        Livewire::test(Index::class)
            ->assertSee('Showing 1 to 10 of 12 entries')
            ->set('assignmentsPerPage', 5)
            ->assertSee('Showing 1 to 5 of 12 entries')
            ->call('nextPage', 'assignmentsPage')
            ->assertSet('paginators.assignmentsPage', 2)
            ->assertSee('Showing 6 to 10 of 12 entries')
            ->assertSee('Showing 1 to 1 of 1 entries')
            ->set('assignmentSearch', 'Paginated Note 11')
            ->assertSet('paginators.assignmentsPage', 1)
            ->assertSee('Paginated Note 11')
            ->assertDontSee('Paginated Note 10');
    }

    public function test_admin_can_add_default_members_without_changing_other_defaults_or_temporary_assignments(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $defaultLocation = WorkLocation::create([
            'name' => 'Kantor Default',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $previousLocation = WorkLocation::create([
            'name' => 'Lokasi Sebelumnya',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $temporaryLocation = WorkLocation::create([
            'name' => 'Site Sementara',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $employeeToAdd = Employee::factory()->active()->create();
        $employeeToRemove = Employee::factory()->active()->create(['work_location_id' => $defaultLocation->id]);
        $employeeToMove = Employee::factory()->active()->create(['work_location_id' => $previousLocation->id]);
        $employeeToRemove->defaultWorkLocations()->attach($defaultLocation->id);
        $employeeToMove->defaultWorkLocations()->attach($previousLocation->id);
        $temporaryAssignment = EmployeeLocationAssignment::create([
            'employee_id' => $employeeToMove->id,
            'work_location_id' => $temporaryLocation->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'notes' => 'Penugasan sementara tetap aktif',
        ]);

        Livewire::test(Index::class)
            ->call('openDefaultEmployeeModal', $defaultLocation->id)
            ->assertDispatched('open-modal', id: 'defaultEmployeesModal')
            ->assertSet('selectedDefaultEmployeeIds', [(string) $employeeToRemove->id])
            ->set('selectedDefaultEmployeeIds', [(string) $employeeToAdd->id, (string) $employeeToMove->id])
            ->call('saveDefaultEmployees')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'defaultEmployeesModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeToAdd->id,
            'work_location_id' => $defaultLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employeeToAdd->id,
            'work_location_id' => $defaultLocation->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employeeToRemove->id,
            'work_location_id' => null,
        ]);
        $this->assertDatabaseMissing('employee_default_work_locations', [
            'employee_id' => $employeeToRemove->id,
            'work_location_id' => $defaultLocation->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employeeToMove->id,
            'work_location_id' => $previousLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employeeToMove->id,
            'work_location_id' => $defaultLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employeeToMove->id,
            'work_location_id' => $previousLocation->id,
        ]);
        $this->assertDatabaseHas('employee_location_assignments', [
            'id' => $temporaryAssignment->id,
            'employee_id' => $employeeToMove->id,
            'work_location_id' => $temporaryLocation->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        $validLocationIds = $employeeToMove->fresh()->getValidWorkLocationsOnDate(now())->pluck('id')->sort()->values()->all();
        $expectedLocationIds = [$defaultLocation->id, $previousLocation->id, $temporaryLocation->id];
        sort($expectedLocationIds);

        $this->assertSame($expectedLocationIds, $validLocationIds);
    }

    public function test_employee_can_have_multiple_default_locations_and_primary_falls_back_when_removed(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $firstLocation = WorkLocation::create([
            'name' => 'Lokasi Default Pertama',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $secondLocation = WorkLocation::create([
            'name' => 'Lokasi Default Kedua',
            'latitude' => -6.5650000,
            'longitude' => 107.7620000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $employee = Employee::factory()->active()->create();

        Livewire::test(Index::class)
            ->call('openDefaultEmployeeModal', $firstLocation->id)
            ->set('selectedDefaultEmployeeIds', [(string) $employee->id])
            ->call('saveDefaultEmployees')
            ->assertHasNoErrors();

        Livewire::test(Index::class)
            ->call('openDefaultEmployeeModal', $secondLocation->id)
            ->assertSet('selectedDefaultEmployeeIds', [])
            ->assertSee('Default saat ini: '.$firstLocation->name)
            ->set('selectedDefaultEmployeeIds', [(string) $employee->id])
            ->call('saveDefaultEmployees')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'work_location_id' => $firstLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employee->id,
            'work_location_id' => $firstLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employee->id,
            'work_location_id' => $secondLocation->id,
        ]);

        Livewire::test(Index::class)
            ->set('defaultEmployeeSearch', $firstLocation->name)
            ->assertSee($employee->full_name)
            ->assertSee('Menampilkan 1 dari 1 karyawan');

        Livewire::test(Index::class)
            ->call('openDefaultEmployeeModal', $firstLocation->id)
            ->assertSet('selectedDefaultEmployeeIds', [(string) $employee->id])
            ->set('selectedDefaultEmployeeIds', [])
            ->call('saveDefaultEmployees')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_default_work_locations', [
            'employee_id' => $employee->id,
            'work_location_id' => $firstLocation->id,
        ]);
        $this->assertDatabaseHas('employee_default_work_locations', [
            'employee_id' => $employee->id,
            'work_location_id' => $secondLocation->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'work_location_id' => $secondLocation->id,
        ]);
    }

    public function test_invalid_employee_ids_are_rejected_when_updating_default_location_members(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $location = WorkLocation::create([
            'name' => 'Lokasi Validasi',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
        $existingEmployee = Employee::factory()->active()->create(['work_location_id' => $location->id]);

        Livewire::test(Index::class)
            ->set('defaultEmployeeLocationId', $location->id)
            ->set('selectedDefaultEmployeeIds', [999999])
            ->call('saveDefaultEmployees')
            ->assertHasErrors(['selectedDefaultEmployeeIds.0' => 'exists'])
            ->assertSee('Pilih karyawan aktif yang valid.');

        $this->assertDatabaseHas('employees', [
            'id' => $existingEmployee->id,
            'work_location_id' => $location->id,
        ]);
    }

    public function test_default_location_employee_list_loads_more_and_search_resets_visible_limit(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $location = WorkLocation::create([
            'name' => 'Lokasi Muat Bertahap',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $employees = collect(range(1, 12))->map(function (int $number): Employee {
            return Employee::factory()->active()->create([
                'full_name' => sprintf('Karyawan %02d', $number),
            ]);
        });

        $component = Livewire::test(Index::class)
            ->call('openDefaultEmployeeModal', $location->id)
            ->assertSet('defaultEmployeeLimit', 10)
            ->assertSee('Menampilkan 10 dari 12 karyawan')
            ->assertSee('Muat 10 Lagi');

        $this->assertCount(10, $component->viewData('defaultLocationEmployees'));

        $component
            ->set('selectedDefaultEmployeeIds', [(string) $employees->first()->id])
            ->call('loadMoreDefaultEmployees')
            ->assertSet('defaultEmployeeLimit', 12)
            ->assertSet('selectedDefaultEmployeeIds', [(string) $employees->first()->id])
            ->assertSee('Menampilkan 12 dari 12 karyawan')
            ->assertDontSee('Muat 10 Lagi');

        $this->assertCount(12, $component->viewData('defaultLocationEmployees'));

        $component
            ->set('defaultEmployeeSearch', 'Karyawan 01')
            ->assertSet('defaultEmployeeLimit', 10)
            ->assertSee('Menampilkan 1 dari 1 karyawan');

        $this->assertCount(1, $component->viewData('defaultLocationEmployees'));
    }

    public function test_can_create_work_location_with_associated_departments(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $dept1 = Department::firstOrCreate(['name' => 'Sintesa Printing Test']);
        $dept2 = Department::firstOrCreate(['name' => 'Sintesa Clothing Test']);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Workshop Gabungan')
            ->set('form.address', 'Jl. Industri No. 99')
            ->set('form.latitude', '-6.5715000')
            ->set('form.longitude', '107.7587000')
            ->set('form.radius_meters', 100)
            ->set('form.is_active', true)
            ->set('form.department_ids', [$dept1->id, $dept2->id])
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'workLocationModal');

        $location = WorkLocation::where('name', 'Workshop Gabungan')->firstOrFail();

        $this->assertDatabaseHas('department_work_locations', [
            'work_location_id' => $location->id,
            'department_id' => $dept1->id,
        ]);
        $this->assertDatabaseHas('department_work_locations', [
            'work_location_id' => $location->id,
            'department_id' => $dept2->id,
        ]);
    }

    public function test_can_update_work_location_associated_departments(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $dept1 = Department::firstOrCreate(['name' => 'Dept A Test']);
        $dept2 = Department::firstOrCreate(['name' => 'Dept B Test']);

        $location = WorkLocation::create([
            'name' => 'Lokasi Edit Dept',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 50,
            'is_active' => true,
        ]);
        $location->departments()->attach($dept1->id);

        Livewire::test(Index::class)
            ->call('edit', $location->id)
            ->assertSet('form.department_ids', [$dept1->id])
            ->set('form.department_ids', [$dept2->id])
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'workLocationModal');

        $this->assertDatabaseMissing('department_work_locations', [
            'work_location_id' => $location->id,
            'department_id' => $dept1->id,
        ]);
        $this->assertDatabaseHas('department_work_locations', [
            'work_location_id' => $location->id,
            'department_id' => $dept2->id,
        ]);
    }

    public function test_can_filter_work_locations_by_department(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $deptFinance = Department::firstOrCreate(['name' => 'Keuangan Khusus']);
        $deptWorkshop = Department::firstOrCreate(['name' => 'Workshop Khusus']);

        $locFinance = WorkLocation::create([
            'name' => 'Gedung Keuangan Khusus',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 50,
            'is_active' => true,
        ]);
        $locFinance->departments()->attach($deptFinance->id);

        $locWorkshop = WorkLocation::create([
            'name' => 'Gedung Workshop Khusus',
            'latitude' => -6.5720000,
            'longitude' => 107.7590000,
            'radius_meters' => 50,
            'is_active' => true,
        ]);
        $locWorkshop->departments()->attach($deptWorkshop->id);

        $component = Livewire::test(Index::class)
            ->set('selectedDepartmentId', $deptFinance->id);

        $locations = $component->viewData('workLocations');
        $this->assertTrue($locations->pluck('name')->contains('Gedung Keuangan Khusus'));
        $this->assertFalse($locations->pluck('name')->contains('Gedung Workshop Khusus'));
    }

    public function test_employee_inherits_department_work_locations_for_attendance(): void
    {
        $dept = Department::firstOrCreate(['name' => 'Dept Auto Geofence']);

        $loc = WorkLocation::create([
            'name' => 'Titik Auto Geofence',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 60,
            'is_active' => true,
        ]);
        $dept->workLocations()->attach($loc->id);

        $employee = Employee::factory()->active()->create([
            'department_id' => $dept->id,
            'work_location_id' => null,
            'is_free_location' => false,
        ]);

        $validLocations = $employee->getValidWorkLocationsOnDate(now());

        $this->assertNotNull($validLocations);
        $this->assertTrue($validLocations->contains('id', $loc->id));
    }
}
