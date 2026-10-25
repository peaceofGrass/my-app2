<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Requests\ReactionRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ReactionController extends Controller
{
    /**
     * Toggle the user's like or dislike on the post.
     */
    public function post(ReactionRequest $request, Post $post): RedirectResponse
    {
        $post->toggleReaction($request->user(), $request->enum('type', ReactionType::class));

        return Redirect::route('posts.show', $post);
    }

    /**
     * Toggle the user's like or dislike on the comment.
     */
    public function comment(ReactionRequest $request, Comment $comment): RedirectResponse
    {
        $comment->toggleReaction($request->user(), $request->enum('type', ReactionType::class));

        return Redirect::route('posts.show', $comment->post_id);
    }
}
