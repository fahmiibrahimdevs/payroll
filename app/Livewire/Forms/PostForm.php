<?php

namespace App\Livewire\Forms;

use App\Models\Post;
use App\Services\PostService;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PostForm extends Form
{
    public ?Post $post = null;

    public string $title = '';

    public string $category = '';

    public string $status = 'draft';

    public string $content = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:draft,published'],
            'content' => ['required', 'string', 'min:5', 'max:50000'],
        ];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'title' => 'Judul Post',
            'category' => 'Kategori',
            'status' => 'Status',
            'content' => 'Konten',
        ];
    }

    /**
     * Populate form fields from an existing Post model for editing.
     */
    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->category = $post->category;
        $this->status = $post->status;
        $this->content = $post->content;
        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset(['post', 'title', 'category', 'content']);
        $this->status = 'draft';
        $this->resetValidation();
    }

    /**
     * Validate and create a new post using the PostService.
     */
    public function store(PostService $postService): Post
    {
        $validated = $this->validate();

        $post = $postService->createPost($validated);
        $this->resetForm();

        return $post;
    }

    /**
     * Validate and update an existing post using the PostService.
     */
    public function update(PostService $postService): Post
    {
        $validated = $this->validate();

        $post = $postService->updatePost($this->post, $validated);
        $this->resetForm();

        return $post;
    }
}
