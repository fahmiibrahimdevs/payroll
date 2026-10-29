<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmptyDatabasePageAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $admin = null;
    protected ?User $direktur = null;
    protected ?User $karyawan = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@payroll.com')->first();
        $this->direktur = User::where('email', 'direktur@payroll.com')->first();
        $this->karyawan = User::where('email', 'karyawan@payroll.com')->first();
    }

    public function test_admin_can_access_all_pages_with_empty_database(): void
    {
        $this->assertNotNull($this->admin);

        $routes = [
            'dashboard' => route('dashboard'),
            'employees.index' => route('employees.index'),
            'departments.index' => route('departments.index'),
            'positions.index' => route('positions.index'),
            'salary-components.index' => route('salary-components.index'),
            'cash-advances.index' => route('cash-advances.index'),
            'attendances.index' => route('attendances.index'),
            'overtimes.index' => route('overtimes.index'),
            'payrolls.index' => route('payrolls.index'),
            'payrolls.weekly' => route('payrolls.weekly'),
            'company-profile.index' => route('company-profile.index'),
            'admin.users' => route('admin.users'),
            'profile' => route('profile'),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302], true),
                "Route {$name} ({$url}) failed with status {$response->status()}"
            );
        }
    }

    public function test_direktur_can_access_allowed_pages_with_empty_database(): void
    {
        $this->assertNotNull($this->direktur);

        $routes = [
            'dashboard' => route('dashboard'),
            'employees.index' => route('employees.index'),
            'departments.index' => route('departments.index'),
            'positions.index' => route('positions.index'),
            'salary-components.index' => route('salary-components.index'),
            'cash-advances.index' => route('cash-advances.index'),
            'attendances.index' => route('attendances.index'),
            'overtimes.index' => route('overtimes.index'),
            'payrolls.index' => route('payrolls.index'),
            'payrolls.weekly' => route('payrolls.weekly'),
            'company-profile.index' => route('company-profile.index'),
            'profile' => route('profile'),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($this->direktur)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302], true),
                "Route {$name} ({$url}) failed with status {$response->status()}"
            );
        }
    }

    public function test_karyawan_can_access_dashboard_and_profile_with_empty_database(): void
    {
        $this->assertNotNull($this->karyawan);

        $response = $this->actingAs($this->karyawan)->get(route('dashboard'));
        $this->assertEquals(200, $response->status());

        $response = $this->actingAs($this->karyawan)->get(route('profile'));
        $this->assertEquals(200, $response->status());
    }
}
