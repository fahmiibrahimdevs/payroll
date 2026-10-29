<?php

namespace Tests\Feature;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Livewire\SalaryComponents\Index;
use App\Models\SalaryComponent;
use App\Services\SalaryComponentService;
use Database\Seeders\SalaryComponentSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SalaryComponentCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('salary-components.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_salary_components_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('salary-components.index'));

        $response->assertOk();
        $response->assertSee('Kelola Komponen Gaji');
    }

    public function test_can_create_allowance_and_logs_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.name', 'Tunjangan Transportasi')
            ->set('form.code', 'TJ-TRP')
            ->set('form.type', SalaryComponentType::ALLOWANCE->value)
            ->set('form.is_active', true)
            ->set('form.description', 'Tunjangan bulanan operasional transportasi')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'salaryComponentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'name' => 'Tunjangan Transportasi',
            'code' => 'TJ-TRP',
            'type' => 'allowance',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'salary_components',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_can_create_deduction_and_logs_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'BPJS Ketenagakerjaan')
            ->set('form.code', 'POT-BPJSTK')
            ->set('form.type', SalaryComponentType::DEDUCTION->value)
            ->set('form.is_active', true)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'salaryComponentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'name' => 'BPJS Ketenagakerjaan',
            'code' => 'POT-BPJSTK',
            'type' => 'deduction',
            'is_active' => true,
        ]);
    }

    public function test_can_update_salary_component_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::factory()->create([
            'name' => 'Tunjangan Makan Harian',
            'code' => 'TJ-MAKAN',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $component->id)
            ->assertSet('form.name', 'Tunjangan Makan Harian')
            ->set('form.name', 'Tunjangan Makan & Minum')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'salaryComponentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'name' => 'Tunjangan Makan & Minum',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'salary_components',
            'action' => 'UPDATE',
            'level' => 'info',
        ]);
    }

    public function test_can_delete_salary_component_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::factory()->create([
            'name' => 'Komponen Hapus',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $component->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('delete', $component->id)
            ->assertDispatched('swal:alert');

        $this->assertSoftDeleted('salary_components', [
            'id' => $component->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'salary_components',
            'action' => 'DELETE',
            'level' => 'info',
        ]);
    }

    public function test_can_toggle_salary_component_status(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::factory()->create([
            'name' => 'Tunjangan Pulsa',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('toggleStatus', $component->id);

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_active' => false,
        ]);

        Livewire::test(Index::class)
            ->call('toggleStatus', $component->id);

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_deactivate_gaji_pokok_system_component(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::where('code', 'GAJI_POKOK')->first()
            ?? SalaryComponent::factory()->system()->create([
                'name' => 'Gaji Pokok',
                'code' => 'GAJI_POKOK',
                'is_system' => true,
                'is_active' => true,
            ]);

        $component->update(['is_active' => true]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('toggleStatus', $component->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_active' => true,
        ]);
    }

    public function test_can_toggle_salary_component_taxable(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::factory()->create([
            'name' => 'Tunjangan Transportasi Tambahan',
            'is_taxable' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('toggleTaxable', $component->id);

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_taxable' => false,
        ]);

        Livewire::test(Index::class)
            ->call('toggleTaxable', $component->id);

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_taxable' => true,
        ]);
    }

    public function test_cannot_remove_taxable_from_gaji_pokok_system_component(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::where('code', 'GAJI_POKOK')->first()
            ?? SalaryComponent::factory()->system()->create([
                'name' => 'Gaji Pokok',
                'code' => 'GAJI_POKOK',
                'is_system' => true,
                'is_taxable' => true,
            ]);

        $component->update(['is_taxable' => true]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('toggleTaxable', $component->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_taxable' => true,
        ]);
    }

    public function test_search_and_filters_return_matching_components(): void
    {
        $user = $this->createAdminUser();
        SalaryComponent::factory()->allowance()->create(['name' => 'Insentif Kehadiran']);
        SalaryComponent::factory()->deduction()->create(['name' => 'Potongan Keterlambatan']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Insentif')
            ->assertSee('Insentif Kehadiran')
            ->assertDontSee('Potongan Keterlambatan')
            ->set('search', '')
            ->set('filterType', 'deduction')
            ->assertSee('Potongan Keterlambatan')
            ->assertDontSee('Insentif Kehadiran');
    }

    public function test_validation_requires_mandatory_fields(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', '')
            ->call('store')
            ->assertHasErrors(['form.name' => 'required']);
    }

    public function test_can_create_multiple_components_with_empty_code_without_unique_collision(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Lembur Hari Kerja')
            ->set('form.code', '')
            ->set('form.type', SalaryComponentType::ALLOWANCE->value)
            ->call('store')
            ->assertHasNoErrors();

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Lembur Hari Libur')
            ->set('form.code', '')
            ->set('form.type', SalaryComponentType::ALLOWANCE->value)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'name' => 'Lembur Hari Kerja',
            'code' => null,
        ]);

        $this->assertDatabaseHas('salary_components', [
            'name' => 'Lembur Hari Libur',
            'code' => null,
        ]);
    }

    public function test_system_components_cannot_be_deleted(): void
    {
        $user = $this->createAdminUser();
        $component = SalaryComponent::factory()->system()->create([
            'name' => 'Gaji Pokok Sistem',
            'code' => 'GAJI_POKOK_TEST',
            'is_system' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $component->id)
            ->assertDispatched('swal:alert')
            ->call('delete', $component->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'deleted_at' => null,
        ]);
    }

    public function test_seeder_populates_19_master_salary_components(): void
    {
        $this->seed(SalaryComponentSeeder::class);

        // Verify total components seeded
        $this->assertGreaterThanOrEqual(19, SalaryComponent::count());

        // Verify active vs inactive according to user spec
        $activeCodes = ['GAJI_POKOK', 'TUNJ_JABATAN', 'TUNJ_MAKAN', 'TUNJ_TRANSPORT', 'LEMBUR', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP', 'KASBON'];
        foreach ($activeCodes as $code) {
            $comp = SalaryComponent::where('code', $code)->first();
            $this->assertNotNull($comp, "Component {$code} must exist");
            $this->assertTrue($comp->is_active, "Component {$code} must be active");
        }

        $inactiveCodes = ['TUNJ_KEAHLIAN', 'TUNJ_MASA_KERJA', 'TUNJ_KELUARGA', 'TUNJ_KOMUNIKASI', 'BONUS', 'THR', 'REIMBURSE', 'PPH21', 'POT_TERLAMBAT', 'POT_ALPA'];
        foreach ($inactiveCodes as $code) {
            $comp = SalaryComponent::where('code', $code)->first();
            $this->assertNotNull($comp, "Component {$code} must exist");
            $this->assertFalse($comp->is_active, "Component {$code} must be inactive");
        }

        // Verify percentage defaults for BPJS
        $bpjsKes = SalaryComponent::where('code', 'BPJS_KES')->first();
        $this->assertEquals(1.00, (float) $bpjsKes->default_value);
        $this->assertEquals('persen_gaji_pokok', $bpjsKes->calculation_type->value);

        $bpjsJht = SalaryComponent::where('code', 'BPJS_JHT')->first();
        $this->assertEquals(2.00, (float) $bpjsJht->default_value);

        $bpjsJp = SalaryComponent::where('code', 'BPJS_JP')->first();
        $this->assertEquals(1.00, (float) $bpjsJp->default_value);

        // Verify system components
        $gajiPokok = SalaryComponent::where('code', 'GAJI_POKOK')->first();
        $this->assertTrue($gajiPokok->is_system);

        $kasbon = SalaryComponent::where('code', 'KASBON')->first();
        $this->assertTrue($kasbon->is_system);

        $tunjMakan = SalaryComponent::where('code', 'TUNJ_MAKAN')->first();
        $this->assertTrue($tunjMakan->is_system);

        $lembur = SalaryComponent::where('code', 'LEMBUR')->first();
        $this->assertTrue($lembur->is_system);
    }

    public function test_active_components_are_ordered_first_in_paginated_list(): void
    {
        $this->seed(SalaryComponentSeeder::class);
        $service = app(SalaryComponentService::class);

        $results = $service->getPaginatedSalaryComponents(perPage: 19);

        // First 9 components must be active (including newly activated LEMBUR)
        $items = $results->items();
        for ($i = 0; $i < 9; $i++) {
            $this->assertTrue($items[$i]->is_active, "Item index {$i} ({$items[$i]->name}) should be active");
        }
        // Remaining components should be inactive
        for ($i = 9; $i < count($items); $i++) {
            $this->assertFalse($items[$i]->is_active, "Item index {$i} ({$items[$i]->name}) should be inactive");
        }
    }

    public function test_director_can_view_and_crud_salary_components(): void
    {
        $director = $this->createDirectorUser();

        $response = $this->actingAs($director)->get(route('salary-components.index'));
        $response->assertOk();

        $this->actingAs($director);

        // Director can create salary component
        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Tunjangan Operasional Direksi')
            ->set('form.type', SalaryComponentType::ALLOWANCE->value)
            ->set('form.category', SalaryComponentCategory::FIXED_ALLOWANCE->value)
            ->set('form.calculation_type', SalaryCalculationType::NOMINAL->value)
            ->set('form.default_value', 1500000)
            ->set('form.is_active', true)
            ->set('form.is_taxable', true)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'salaryComponentModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('salary_components', [
            'name' => 'Tunjangan Operasional Direksi',
            'default_value' => 1500000,
        ]);

        $comp = SalaryComponent::where('name', 'Tunjangan Operasional Direksi')->first();

        // Director can update salary component
        Livewire::test(Index::class)
            ->call('edit', $comp->id)
            ->set('form.default_value', 2000000)
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'salaryComponentModal');

        $this->assertDatabaseHas('salary_components', [
            'id' => $comp->id,
            'default_value' => 2000000,
        ]);

        // Director can delete custom salary component
        Livewire::test(Index::class)
            ->call('delete', $comp->id)
            ->assertDispatched('swal:alert');

        $this->assertSoftDeleted('salary_components', [
            'id' => $comp->id,
        ]);
    }

    public function test_karyawan_cannot_crud_salary_components_and_is_system_is_immutable(): void
    {
        $karyawan = $this->createKaryawanUser();
        $this->actingAs($karyawan);

        // Karyawan route access is forbidden
        $response = $this->get(route('salary-components.index'));
        $response->assertForbidden();

        // Direct Livewire mount is also forbidden
        Livewire::test(Index::class)
            ->assertForbidden();
    }

    public function test_is_system_flag_cannot_be_tampered_via_client(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Create component trying to set is_system = true
        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Tunjangan Khusus Coba Sistem')
            ->set('form.type', SalaryComponentType::ALLOWANCE->value)
            ->set('form.category', SalaryComponentCategory::FIXED_ALLOWANCE->value)
            ->set('form.calculation_type', SalaryCalculationType::NOMINAL->value)
            ->set('form.default_value', 100000)
            ->set('form.is_system', true) // Injected tampering
            ->call('store');

        $comp = SalaryComponent::where('name', 'Tunjangan Khusus Coba Sistem')->first();
        $this->assertNotNull($comp);
        // Must remain false!
        $this->assertFalse((bool) $comp->is_system);
    }
}
