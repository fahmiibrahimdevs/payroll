<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_new_users_can_register(): void
    {
        $email = fake()->unique()->safeEmail();

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'is_active' => false,
        ]);
    }
}
