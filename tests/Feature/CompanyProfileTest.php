<?php

namespace Tests\Feature;

use App\Livewire\CompanyProfile\Index;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('company-profile.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_access_company_profile(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('company-profile.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_and_update_company_profile(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('company-profile.index'));
        $response->assertOk();

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('name', 'PT Larashiz Solusindo')
            ->set('email', 'corporate@larashiz.id')
            ->set('city', 'Bandung')
            ->set('province', 'Jawa Barat')
            ->set('postal_code', '40115')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'name' => 'PT Larashiz Solusindo',
            'email' => 'corporate@larashiz.id',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40115',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'module' => 'company_profile',
            'action' => 'PROFILE_UPDATED',
        ]);
    }

    public function test_director_can_view_and_update_company_profile(): void
    {
        $director = $this->createDirectorUser();

        $response = $this->actingAs($director)->get(route('company-profile.index'));
        $response->assertOk();

        $this->actingAs($director);

        Livewire::test(Index::class)
            ->set('name', 'PT Larashiz Multi Niaga')
            ->set('phone', '021-98765432')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'name' => 'PT Larashiz Multi Niaga',
            'phone' => '021-98765432',
        ]);
    }

    public function test_admin_can_update_email_domain_and_generate_employee_email(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('email_domain', '@perusahaananda.co.id')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'email_domain' => 'perusahaananda.co.id',
        ]);

        $generated = CompanyProfile::generateEmployeeEmail('Arif Fahmi Abdillah');
        $this->assertSame('arif.fahmi.abdillah@perusahaananda.co.id', $generated);
    }

    public function test_admin_can_update_weekly_work_days_setting(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('work_days_per_week', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'work_days_per_week' => 5,
        ]);
    }

    public function test_admin_can_update_payroll_cutoff_and_pay_day_settings(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('payroll_cutoff_day', 22)
            ->set('payroll_pay_day', 27)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'payroll_cutoff_day' => 22,
            'payroll_pay_day' => 27,
        ]);
    }
}
