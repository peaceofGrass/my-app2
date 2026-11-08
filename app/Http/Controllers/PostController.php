<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a paginated list of published posts, newest first, optionally filtered by a search (?q=).
     */
    public function index(Request $request): Response
    {
        $input = $request->query('q');
        $search = is_string($input) ? mb_substr(Str::squish($input), 0, Post::SEARCH_MAX_LENGTH) : '';

        $posts = Post::query()
            ->where('is_published', true)
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->with(['user:id,name', 'attachments'])
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Post $post) => $post->append('excerpt')->makeHidden('content'));

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'filters' => ['q' => $search],
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
     * Store a newly created post, its gallery images, and its editor images for the authenticated user.
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $post = DB::transaction(function () use ($request) {
            $post = $request->user()->posts()->create(
                $request->safe()->only(['title', 'content', 'is_published']),
            );

            $post->storeAttachments($request->file('attachments', []));
            $post->syncImages();

            return $post;
        });

        return Redirect::route('posts.show', $post);
    }

    /**
     * Display the post with its author, attachments, reactions, and comment tree.
     */
    public function show(Request $request, Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load(['user:id,name', 'attachments'])->loadReactionSummary($request->user());

        return Inertia::render('Posts/Show', [
            'post' => $post,
            'comments' => $this->commentTree($post, $request->user()),
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
     * Update the post, removing selected gallery images, storing new ones, and syncing editor images.
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
            $post->syncImages();
        });

        return Redirect::route('posts.show', $post);
    }

    /**
     * Delete the post along with its comments, their reactions, and every attachment file.
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

            Reaction::query()
                ->whereMorphedTo('reactable', $post)
                ->orWhereHasMorph('reactable', Comment::class, fn (Builder $query) => $query->whereBelongsTo($post))
                ->delete();

            // The foreign key cascade removes the rows but not the files, so purge editor images first.
            PostImage::purge($post->images()->get());

            $post->delete();
        });

        return Redirect::route('posts.index');
    }

    /**
     * Load all of the post's comments with their reaction summary in one query and nest them by parent_id.
     *
     * @return Collection<int, Comment>
     */
    private function commentTree(Post $post, ?User $user): Collection
    {
        $comments = $post->comments()
            ->with(['user:id,name', 'attachments'])
            ->withReactionSummary($user)
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
