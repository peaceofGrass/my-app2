<?php

use App\Enums\ReactionType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot react to posts or comments', function () {
    $comment = Comment::factory()->for(Post::factory()->published())->create();

    $this->post("/posts/{$comment->post_id}/reaction", ['type' => 'like'])->assertRedirect('/login');
    $this->post("/comments/{$comment->id}/reaction", ['type' => 'like'])->assertRedirect('/login');

    expect(Reaction::count())->toBe(0);
});

test('users can like a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)
        ->post("/posts/{$post->id}/reaction", ['type' => 'like'])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/posts/{$post->id}");

    $reaction = $post->reactions()->sole();

    expect($reaction->user_id)->toBe($user->id)
        ->and($reaction->type)->toBe(ReactionType::Like);
});

test('choosing the other reaction switches it', function () {
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'like']);
    $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'dislike']);

    expect($post->reactions()->sole()->type)->toBe(ReactionType::Dislike);

    $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'like']);

    expect($post->reactions()->sole()->type)->toBe(ReactionType::Like);
});

test('choosing the same reaction again cancels it', function (string $type) {
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => $type]);
    $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => $type]);

    expect($post->reactions()->count())->toBe(0);
})->with(['like', 'dislike']);

test('users can react to comments', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->for(Post::factory()->published())->create();

    $this->actingAs($user)
        ->post("/comments/{$comment->id}/reaction", ['type' => 'dislike'])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/posts/{$comment->post_id}");

    expect($comment->reactions()->sole()->type)->toBe(ReactionType::Dislike);

    $this->actingAs($user)->post("/comments/{$comment->id}/reaction", ['type' => 'dislike']);

    expect($comment->reactions()->count())->toBe(0);
});

test('the reaction type must be like or dislike', function () {
    $post = Post::factory()->published()->create();

    $this->actingAs(User::factory()->create())
        ->post("/posts/{$post->id}/reaction", ['type' => 'love'])
        ->assertSessionHasErrors('type');

    expect(Reaction::count())->toBe(0);
});

test('users cannot react to drafts they cannot see or their comments', function () {
    $comment = Comment::factory()->for(Post::factory()->draft())->create();

    $this->actingAs(User::factory()->create())
        ->post("/posts/{$comment->post_id}/reaction", ['type' => 'like'])
        ->assertNotFound();
    $this->actingAs(User::factory()->create())
        ->post("/comments/{$comment->id}/reaction", ['type' => 'like'])
        ->assertNotFound();

    expect(Reaction::count())->toBe(0);
});

test('the post page shows reaction counts and the viewer\'s own reaction', function () {
    $viewer = User::factory()->create();
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->for($post)->create();
    $reply = Comment::factory()->replyTo($comment)->create();

    Reaction::factory()->count(2)->for($post, 'reactable')->create();
    Reaction::factory()->dislike()->for($post, 'reactable')->create();
    Reaction::factory()->for($post, 'reactable')->for($viewer)->create();
    Reaction::factory()->dislike()->for($reply, 'reactable')->for($viewer)->create();

    $this->actingAs($viewer)
        ->get("/posts/{$post->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.likes_count', 3)
            ->where('post.dislikes_count', 1)
            ->where('post.my_reaction', 'like')
            ->where('comments.0.likes_count', 0)
            ->where('comments.0.my_reaction', null)
            ->where('comments.0.children.0.dislikes_count', 1)
            ->where('comments.0.children.0.my_reaction', 'dislike'),
        );

    $this->post('/logout');

    $this->get("/posts/{$post->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.likes_count', 3)
            ->where('post.my_reaction', null)
            ->where('comments.0.children.0.my_reaction', null),
        );
});

test('deleting a post or comment removes its reactions', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->for($post)->create();
    $reply = Comment::factory()->replyTo($comment)->create();
    $other = Reaction::factory()->create();

    Reaction::factory()->for($reply, 'reactable')->create();

    $this->actingAs($comment->user)->delete("/comments/{$comment->id}");

    expect(Reaction::pluck('id')->all())->toBe([$other->id]);

    Reaction::factory()->for($post, 'reactable')->create();
    Reaction::factory()->for(Comment::factory()->for($post), 'reactable')->create();

    $this->actingAs($post->user)->delete("/posts/{$post->id}");

    expect(Reaction::pluck('id')->all())->toBe([$other->id]);
});
