<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PostService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated posts with optional search, status, and category filtering.
     */
    public function getPaginatedPosts(
        string $search = '',
        string $status = '',
        string $category = '',
        int $perPage = 10
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        return Post::query()
            ->when($search, fn ($q) => $q->search($search))
            ->when($status, fn ($q) => $q->status($status))
            ->when($category, fn ($q) => $q->category($category))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get distinct post categories for filter options.
     *
     * @return array<string>
     */
    public function getDistinctCategories(): array
    {
        return Post::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Create a new post.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createPost(array $data): Post
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['slug'] = $this->uniqueSlug((string) ($data['slug'] ?? ''), (string) ($data['title'] ?? ''));

                $post = Post::create($data);

                $this->logger->info(
                    action: 'CREATE',
                    module: 'posts',
                    message: 'Post created',
                    context: [
                        'post_id' => $post->id,
                        'title' => $post->title,
                        'category' => $post->category,
                        'status' => $post->status,
                    ]
                );

                return $post;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'posts',
                message: 'Post creation failed',
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing post.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updatePost(Post $post, array $data): Post
    {
        try {
            return DB::transaction(function () use ($post, $data) {
                $oldValues = $post->only(['title', 'category', 'status', 'content']);

                if (isset($data['title']) && $data['title'] !== $post->title) {
                    $data['slug'] = $this->uniqueSlug('', (string) $data['title'], $post->id);
                }

                $post->update($data);

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'posts',
                    message: 'Post updated',
                    context: [
                        'post_id' => $post->id,
                        'old_values' => $oldValues,
                        'new_values' => $post->only(['title', 'category', 'status', 'content']),
                    ]
                );

                return $post;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'posts',
                message: 'Post update failed',
                context: [
                    'post_id' => $post->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Delete a post.
     *
     * @throws Throwable
     */
    public function deletePost(Post $post): bool
    {
        try {
            return DB::transaction(function () use ($post) {
                $deletedInfo = [
                    'post_id' => $post->id,
                    'title' => $post->title,
                    'category' => $post->category,
                ];

                $deleted = $post->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'posts',
                    message: 'Post deleted',
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'posts',
                message: 'Post deletion failed',
                context: [
                    'post_id' => $post->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    private function uniqueSlug(string $slug, string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug !== '' ? $slug : $title) ?: 'post';
        $candidate = $baseSlug;
        $suffix = 2;

        while (Post::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
