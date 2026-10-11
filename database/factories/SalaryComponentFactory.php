<?php

namespace Database\Factories;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryComponent>
 */
class SalaryComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement([
            SalaryComponentCategory::FIXED_ALLOWANCE,
            SalaryComponentCategory::VARIABLE_ALLOWANCE,
            SalaryComponentCategory::DEDUCTION,
        ]);

        return [
            'name' => fake()->unique()->words(2, true),
            'code' => 'SC-'.fake()->unique()->numerify('###'),
            'category' => $category,
            'type' => $category->type(),
            'calculation_type' => SalaryCalculationType::NOMINAL,
            'default_value' => null,
            'is_taxable' => $category->isAllowance(),
            'is_system' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 99),
            'description' => fake()->sentence(8),
        ];
    }

    /**
     * Indicate that the component is an allowance.
     */
    public function allowance(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
            'type' => SalaryComponentType::ALLOWANCE,
            'is_taxable' => true,
        ]);
    }

    /**
     * Indicate that the component is a deduction.
     */
    public function deduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => SalaryComponentCategory::DEDUCTION,
            'type' => SalaryComponentType::DEDUCTION,
            'is_taxable' => false,
        ]);
    }

    /**
     * Indicate that the component is a system component.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }
}
