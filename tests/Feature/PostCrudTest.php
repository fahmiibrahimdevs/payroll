<?php

namespace Tests\Feature;

use App\Livewire\Posts\Index;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('posts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_posts_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('posts.index'));

        $response->assertOk();
        $response->assertSee('Kelola Posts');
    }

    public function test_can_create_post_and_logs_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.title', 'Belajar Clean Architecture')
            ->set('form.category', 'Technology')
            ->set('form.status', 'published')
            ->set('form.content', 'Clean Architecture memisahkan concern dengan rapi.')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'postModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('posts', [
            'title' => 'Belajar Clean Architecture',
            'category' => 'Technology',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_can_update_post_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $post = Post::factory()->create([
            'title' => 'Judul Lama',
            'category' => 'Technology',
            'status' => 'draft',
            'content' => 'Konten lama.',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $post->id)
            ->assertSet('form.title', 'Judul Lama')
            ->set('form.title', 'Judul Baru yang Diperbarui')
            ->set('form.status', 'published')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'postModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Judul Baru yang Diperbarui',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'UPDATE',
            'level' => 'info',
        ]);
    }

    public function test_can_delete_post_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $post = Post::factory()->create([
            'title' => 'Post yang Akan Dihapus',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $post->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('delete', $post->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'posts',
            'action' => 'DELETE',
            'level' => 'info',
        ]);
    }

    public function test_search_filter_returns_matching_posts(): void
    {
        $user = $this->createAdminUser();
        Post::factory()->create(['title' => 'Tutorial Laravel Livewire']);
        Post::factory()->create(['title' => 'Belajar Vue JS']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Livewire')
            ->assertSee('Tutorial Laravel Livewire')
            ->assertDontSee('Belajar Vue JS');
    }
}
