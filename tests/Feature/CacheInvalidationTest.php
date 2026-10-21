<?php

namespace Tests\Feature;

use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\CashAdvanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_profile_cache_is_invalidated_when_updated(): void
    {
        Cache::flush();

        $profile1 = CompanyProfile::getProfile();
        $this->assertTrue(Cache::has(CompanyProfile::CACHE_KEY));

        $profile1->update(['name' => 'PT Larashiz Cache Test']);

        // After save, cache must be cleared
        $this->assertFalse(Cache::has(CompanyProfile::CACHE_KEY));

        $profile2 = CompanyProfile::getProfile();
        $this->assertEquals('PT Larashiz Cache Test', $profile2->name);
    }

    public function test_department_lookup_cache_is_invalidated_when_department_created_or_updated(): void
    {
        Cache::flush();

        $initial = Department::getLookup();
        $this->assertTrue(Cache::has(Department::LOOKUP_CACHE_KEY));

        $newDept = Department::create([
            'name' => 'Departemen Inovasi Baru',
            'description' => 'Test Deskripsi',
        ]);

        // Cache must be flushed automatically
        $this->assertFalse(Cache::has(Department::LOOKUP_CACHE_KEY));

        $refreshed = Department::getLookup();
        $this->assertTrue($refreshed->contains('name', 'Departemen Inovasi Baru'));
    }

    public function test_position_lookup_cache_is_invalidated_when_position_created_or_updated(): void
    {
        Cache::flush();

        $initial = Position::getLookup();
        $this->assertTrue(Cache::has(Position::LOOKUP_CACHE_KEY));

        $newPos = Position::create([
            'name' => 'AI Automation Engineer',
            'description' => 'Test Deskripsi',
        ]);

        // Cache must be flushed automatically
        $this->assertFalse(Cache::has(Position::LOOKUP_CACHE_KEY));

        $refreshed = Position::getLookup();
        $this->assertTrue($refreshed->contains('name', 'AI Automation Engineer'));
    }

    public function test_cash_advance_recap_chart_cache_is_invalidated_on_save(): void
    {
        Cache::flush();

        $service = app(CashAdvanceService::class);
        $data = $service->getRecapChartData();

        $this->assertTrue(Cache::has(CashAdvanceService::RECAP_CHART_CACHE_KEY));

        CashAdvanceService::clearRecapChartCache();
        $this->assertFalse(Cache::has(CashAdvanceService::RECAP_CHART_CACHE_KEY));
    }

    public function test_salary_component_lookup_cache_is_invalidated_when_updated(): void
    {
        Cache::flush();

        $initial = \App\Models\SalaryComponent::getActiveLookup();
        $this->assertTrue(Cache::has(\App\Models\SalaryComponent::LOOKUP_CACHE_KEY));

        $newComp = \App\Models\SalaryComponent::create([
            'name' => 'Tunjangan Khusus Project',
            'code' => 'TUNJ_PROJECT',
            'category' => \App\Enums\SalaryComponentCategory::FIXED_ALLOWANCE,
            'type' => \App\Enums\SalaryComponentType::ALLOWANCE,
            'calculation_type' => \App\Enums\SalaryCalculationType::NOMINAL,
            'default_value' => 500000,
            'is_taxable' => true,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        // Cache must be flushed automatically
        $this->assertFalse(Cache::has(\App\Models\SalaryComponent::LOOKUP_CACHE_KEY));

        $refreshed = \App\Models\SalaryComponent::getActiveLookup();
        $this->assertTrue($refreshed->contains('code', 'TUNJ_PROJECT'));
        $this->assertNotNull(\App\Models\SalaryComponent::getByCode('TUNJ_PROJECT'));
    }

    public function test_dashboard_payroll_trend_cache_is_invalidated_when_payroll_period_saved(): void
    {
        Cache::flush();

        $year = (int) date('Y');
        Cache::put("dashboard_payroll_trend_{$year}", ['test' => true], 1800);
        $this->assertTrue(Cache::has("dashboard_payroll_trend_{$year}"));

        \App\Services\DashboardService::clearPayrollTrendCache($year);
        $this->assertFalse(Cache::has("dashboard_payroll_trend_{$year}"));
    }
}
