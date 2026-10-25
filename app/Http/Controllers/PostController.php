<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a paginated list of published posts, newest first.
     */
    public function index(): Response
    {
        $posts = Post::query()
            ->where('is_published', true)
            ->with(['user:id,name', 'attachments'])
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): Response
    {
        return Inertia::render('Posts/Create');
    }

    /**
     * Store a newly created post and its uploaded images for the authenticated user.
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $post = DB::transaction(function () use ($request) {
            $post = $request->user()->posts()->create(
                $request->safe()->only(['title', 'content', 'is_published']),
            );

            $post->storeAttachments($request->file('attachments', []));

            return $post;
        });

        return Redirect::route('posts.show', $post);
    }

    /**
     * Display the post with its author, attachments, and comment tree.
     */
    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load(['user:id,name', 'attachments']);

        return Inertia::render('Posts/Show', [
            'post' => $post,
            'comments' => $this->commentTree($post),
        ]);
    }

    /**
     * Show the form for editing the post.
     */
    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        $post->load('attachments');

        return Inertia::render('Posts/Edit', [
            'post' => $post,
        ]);
    }

    /**
     * Update the post, removing selected images and storing new ones.
     * Authorization is handled by PostRequest.
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        DB::transaction(function () use ($request, $post) {
            $post->update($request->safe()->only(['title', 'content', 'is_published']));

            Attachment::purge(
                $post->attachments()->whereKey($request->validated('remove_attachments', []))->get(),
            );

            $post->storeAttachments($request->file('attachments', []));
        });

        return Redirect::route('posts.show', $post);
    }

    /**
     * Delete the post along with its comments and every attachment file.
     */
    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        DB::transaction(function () use ($post) {
            // Polymorphic attachments have no foreign key, so remove them explicitly.
            Attachment::purge(
                Attachment::query()
                    ->whereMorphedTo('attachable', $post)
                    ->orWhereHasMorph('attachable', Comment::class, fn (Builder $query) => $query->whereBelongsTo($post))
                    ->get(),
            );

            $post->delete();
        });

        return Redirect::route('posts.index');
    }

    /**
     * Load all of the post's comments in one query and nest them by parent_id.
     *
     * @return Collection<int, Comment>
     */
    private function commentTree(Post $post): Collection
    {
        $comments = $post->comments()
            ->with(['user:id,name', 'attachments'])
            ->oldest()
            ->oldest('id')
            ->get();

        $childrenByParent = $comments->groupBy('parent_id');

        $comments->each(fn (Comment $comment) => $comment->setRelation(
            'children',
            $childrenByParent->get($comment->id, new Collection),
        ));

        return $comments->whereNull('parent_id')->values();
    }
}
