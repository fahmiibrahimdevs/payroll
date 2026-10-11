<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDetail>
 */
class EmployeeDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'gender' => fake()->randomElement([Gender::MALE, Gender::FEMALE]),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'bank_name' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI']),
            'bank_account_number' => fake()->bankAccountNumber(),
            'bank_account_name' => fake()->name(),
            'curriculum_vitae' => null,
        ];
    }
}
