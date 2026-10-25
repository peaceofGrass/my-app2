<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class CommentController extends Controller
{
    /**
     * Store a comment, or a reply when parent_id is given, with its uploaded images.
     */
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        DB::transaction(function () use ($request, $post) {
            $comment = $post->comments()
                ->make($request->safe()->only(['content', 'parent_id']))
                ->user()->associate($request->user());

            $comment->save();

            $comment->storeAttachments($request->file('attachments', []));
        });

        return Redirect::route('posts.show', $post);
    }

    /**
     * Update the comment. Authorization is handled by UpdateCommentRequest.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return Redirect::route('posts.show', $comment->post_id);
    }

    /**
     * Delete the comment, its nested replies, and every attachment file among them.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        DB::transaction(function () use ($comment) {
            // Replies are removed by the parent_id cascade, but their attachments are not.
            Attachment::purge(
                Attachment::query()
                    ->where('attachable_type', $comment->getMorphClass())
                    ->whereIn('attachable_id', [$comment->id, ...$comment->descendantIds()])
                    ->get(),
            );

            $comment->delete();
        });

        return Redirect::route('posts.show', $comment->post_id);
    }
}
