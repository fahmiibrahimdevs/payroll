<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'full_name' => fake()->name(),
            'join_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'contract_end_date' => fake()->optional(0.5)->dateTimeBetween('now', '+2 years')?->format('Y-m-d'),
            'employment_status' => fake()->randomElement([EmploymentStatus::TETAP, EmploymentStatus::KONTRAK, EmploymentStatus::MAGANG]),
            'status' => fake()->randomElement([EmployeeStatus::ACTIVE, EmployeeStatus::INACTIVE]),
            'basic_salary' => fake()->numberBetween(4000000, 20000000),
            'total_cash_receipt' => fake()->numberBetween(0, 5000000),
            'photo_path' => null,
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::INACTIVE,
        ]);
    }

    /**
     * Configure the factory to create a related employee detail automatically.
     */
    public function withDetail(array $detailAttributes = []): static
    {
        return $this->afterCreating(function (Employee $employee) use ($detailAttributes) {
            EmployeeDetail::factory()->create(array_merge([
                'employee_id' => $employee->id,
            ], $detailAttributes));
        });
    }
}
