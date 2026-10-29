<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'users',
            'action' => 'PROFILE_UPDATED',
        ]);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }

    public function test_karyawan_cannot_delete_their_own_account(): void
    {
        $karyawan = $this->createKaryawanUser();
        $this->actingAs($karyawan);

        // Accessing delete action must return 403 Forbidden
        Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertForbidden();

        $this->assertTrue($karyawan->fresh()->is_active);
    }

    public function test_karyawan_cannot_tamper_with_email_via_profile(): void
    {
        $karyawan = $this->createKaryawanUser();
        $originalEmail = $karyawan->email;
        $this->actingAs($karyawan);

        // Attempt to tamper with email via profile
        Volt::test('profile.update-profile-information-form')
            ->set('name', 'Nama Karyawan Update')
            ->set('email', 'tampered_email@gmail.com')
            ->call('updateProfileInformation');

        $karyawan->refresh();
        $this->assertEquals('Nama Karyawan Update', $karyawan->name);
        // Email must remain original
        $this->assertEquals($originalEmail, $karyawan->email);
    }
}
