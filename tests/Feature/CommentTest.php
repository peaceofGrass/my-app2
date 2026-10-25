<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot create, update, or delete comments', function () {
    $comment = Comment::factory()->create();

    $this->post("/posts/{$comment->post_id}/comments", ['content' => 'Hi'])->assertRedirect('/login');
    $this->patch("/comments/{$comment->id}", ['content' => 'Hi'])->assertRedirect('/login');
    $this->delete("/comments/{$comment->id}")->assertRedirect('/login');

    expect(Comment::count())->toBe(1);
});

test('users can comment on a post with images', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)
        ->post("/posts/{$post->id}/comments", [
            'content' => 'Nice post',
            'attachments' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/posts/{$post->id}");

    $comment = $post->comments()->sole();

    expect($comment->user_id)->toBe($user->id)
        ->and($comment->parent_id)->toBeNull()
        ->and($comment->attachments)->toHaveCount(2);
    Storage::disk('public')->assertExists($comment->attachments->first()->file_path);
});

test('users can reply to a comment', function () {
    $parent = Comment::factory()->for(Post::factory()->published())->create();

    $this->actingAs(User::factory()->create())
        ->post("/posts/{$parent->post_id}/comments", ['content' => 'Reply', 'parent_id' => $parent->id])
        ->assertSessionHasNoErrors();

    expect($parent->children()->sole()->content)->toBe('Reply');
});

test('a reply must target a comment on the same post', function () {
    $post = Post::factory()->published()->create();
    $otherComment = Comment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post("/posts/{$post->id}/comments", ['content' => 'Reply', 'parent_id' => $otherComment->id])
        ->assertSessionHasErrors('parent_id');
});

test('users cannot comment on drafts they cannot see', function () {
    $draft = Post::factory()->draft()->create();

    $this->actingAs(User::factory()->create())
        ->post("/posts/{$draft->id}/comments", ['content' => 'Hi'])
        ->assertNotFound();

    $this->actingAs($draft->user)
        ->post("/posts/{$draft->id}/comments", ['content' => 'Hi'])
        ->assertSessionHasNoErrors();
});

test('authors can update their comments', function () {
    $comment = Comment::factory()->create();

    $this->actingAs($comment->user)
        ->patch("/comments/{$comment->id}", ['content' => 'Edited'])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/posts/{$comment->post_id}");

    expect($comment->refresh()->content)->toBe('Edited');
});

test('users cannot update or delete comments they do not own', function () {
    $comment = Comment::factory()->create(['content' => 'Original']);
    $other = User::factory()->create();

    $this->actingAs($other)->patch("/comments/{$comment->id}", ['content' => 'Hacked'])->assertForbidden();
    $this->actingAs($other)->delete("/comments/{$comment->id}")->assertForbidden();

    expect($comment->refresh()->content)->toBe('Original');
});

test('deleting a comment removes its nested replies and their attachment files', function () {
    Storage::fake('public');

    $comment = Comment::factory()->create();
    $reply = Comment::factory()->replyTo($comment)->create();
    $nested = Comment::factory()->replyTo($reply)->create();
    $sibling = Comment::factory()->for($comment->post)->create();

    $files = collect([$comment, $nested, $sibling])
        ->map(fn (Comment $owner) => Attachment::factory()->for($owner, 'attachable')->create())
        ->each(fn (Attachment $attachment) => Storage::disk('public')->put($attachment->file_path, 'x'));

    $this->actingAs($comment->user)
        ->delete("/comments/{$comment->id}")
        ->assertRedirect("/posts/{$comment->post_id}");

    $this->assertModelMissing($comment);
    $this->assertModelMissing($reply);
    $this->assertModelMissing($nested);
    $this->assertModelExists($sibling);

    [$commentFile, $nestedFile, $siblingFile] = $files->all();
    $this->assertModelMissing($commentFile);
    $this->assertModelMissing($nestedFile);
    Storage::disk('public')->assertMissing([$commentFile->file_path, $nestedFile->file_path]);
    Storage::disk('public')->assertExists($siblingFile->file_path);
});
