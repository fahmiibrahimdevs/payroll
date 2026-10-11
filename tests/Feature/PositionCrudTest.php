<?php

namespace Tests\Feature;

use App\Livewire\Positions\Index;
use App\Models\Position;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PositionCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('positions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_positions_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('Kelola Jabatan');
    }

    public function test_can_create_position_and_logs_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.name', 'Senior Backend Engineer')
            ->set('form.description', 'Bertanggung jawab atas arsitektur sistem backend.')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'positionModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('positions', [
            'name' => 'Senior Backend Engineer',
            'description' => 'Bertanggung jawab atas arsitektur sistem backend.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'positions',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_can_update_position_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $position = Position::factory()->create([
            'name' => 'HR Specialist',
            'description' => 'Deskripsi jabatan lama.',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $position->id)
            ->assertSet('form.name', 'HR Specialist')
            ->set('form.name', 'HR Business Partner')
            ->set('form.description', 'Deskripsi jabatan baru.')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'positionModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'HR Business Partner',
            'description' => 'Deskripsi jabatan baru.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'positions',
            'action' => 'UPDATE',
            'level' => 'info',
        ]);
    }

    public function test_can_delete_position_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $position = Position::factory()->create([
            'name' => 'Quality Assurance Lead',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $position->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('delete', $position->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('positions', [
            'id' => $position->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'positions',
            'action' => 'DELETE',
            'level' => 'info',
        ]);
    }

    public function test_search_filter_returns_matching_positions(): void
    {
        $user = $this->createAdminUser();
        Position::factory()->create(['name' => 'Project Manager']);
        Position::factory()->create(['name' => 'UI/UX Designer']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Project')
            ->assertSee('Project Manager')
            ->assertDontSee('UI/UX Designer');
    }

    public function test_position_name_must_be_unique(): void
    {
        $user = $this->createAdminUser();
        Position::factory()->create(['name' => 'DevOps Engineer']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'DevOps Engineer')
            ->call('store')
            ->assertHasErrors(['form.name' => 'unique']);
    }
}
