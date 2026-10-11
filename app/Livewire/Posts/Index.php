<?php

namespace App\Livewire\Posts;

use App\Livewire\Forms\PostForm;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Posts')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public PostForm $form;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterCategory = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingPostId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }

    /**
     * Reset pagination when search input changes.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filter status changes.
     */
    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filter category changes.
     */
    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Open modal for creating a new post.
     */
    public function create(): void
    {
        $this->isEditMode = false;
        $this->editingPostId = null;
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => 'postModal',
            'category' => '',
            'status' => 'draft',
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(PostService $postService): void
    {
        try {
            $this->form->store($postService);
            $this->dispatch('close-modal', id: 'postModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Post baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat membuat post.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing post.
     */
    public function edit(int $id): void
    {
        try {
            $post = Post::findOrFail($id);
            $this->isEditMode = true;
            $this->editingPostId = $id;
            $this->form->setPost($post);
            $this->dispatch('open-modal', [
                'id' => 'postModal',
                'category' => $post->category,
                'status' => $post->status,
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data post tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing post.
     */
    public function update(PostService $postService): void
    {
        try {
            $this->form->update($postService);
            $this->isEditMode = false;
            $this->editingPostId = null;
            $this->dispatch('close-modal', id: 'postModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Post berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui post.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        $post = Post::find($id);

        if (! $post) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Post tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $post->title,
        ]);
    }

    /**
     * Delete a post after confirmation.
     */
    #[On('delete-post')]
    public function delete(int $id, PostService $postService): void
    {
        try {
            $post = Post::findOrFail($id);
            $postService->deletePost($post);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Post berhasil dihapus!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus post.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(PostService $postService): View
    {
        $posts = $postService->getPaginatedPosts(
            search: $this->search,
            status: $this->filterStatus,
            category: $this->filterCategory,
            perPage: $this->perPage
        );

        $categories = $postService->getDistinctCategories();

        return view('livewire.posts.index', [
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }
}
