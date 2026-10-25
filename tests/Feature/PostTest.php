<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('index lists only published posts, newest first, with author and attachments', function () {
    $older = Post::factory()->published()->has(Attachment::factory(), 'attachments')->create(['created_at' => now()->subDay()]);
    $newer = Post::factory()->published()->create();
    Post::factory()->draft()->create();

    $this->get('/posts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Index')
            ->has('posts.data', 2)
            ->where('posts.data.0.id', $newer->id)
            ->where('posts.data.1.id', $older->id)
            ->has('posts.data.0.user', fn (Assert $user) => $user->hasAll(['id', 'name'])->missing('email'))
            ->has('posts.data.1.attachments', 1)
        );
});

test('index is paginated', function () {
    Post::factory(12)->published()->create();

    $this->get('/posts?page=2')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 2)
            ->where('posts.current_page', 2)
            ->where('posts.total', 12)
        );
});

test('show returns the post with a nested comment tree', function () {
    $post = Post::factory()->published()->create();
    $root = Comment::factory()->for($post)->create();
    $reply = Comment::factory()->replyTo($root)->create();
    Comment::factory()->replyTo($reply)->create();

    $this->get("/posts/{$post->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Show')
            ->where('post.id', $post->id)
            ->has('post.user')
            ->has('post.attachments')
            ->has('comments', 1)
            ->where('comments.0.id', $root->id)
            ->where('comments.0.children.0.id', $reply->id)
            ->has('comments.0.children.0.children', 1)
        );
});

test('drafts are hidden from everyone but their author', function () {
    $post = Post::factory()->draft()->create();

    $this->get("/posts/{$post->id}")->assertNotFound();
    $this->actingAs(User::factory()->create())->get("/posts/{$post->id}")->assertNotFound();
    $this->actingAs($post->user)->get("/posts/{$post->id}")->assertOk();
});

test('guests cannot create, update, or delete posts', function () {
    $post = Post::factory()->create();

    $this->get('/posts/create')->assertRedirect('/login');
    $this->get("/posts/{$post->id}/edit")->assertRedirect('/login');

    $this->post('/posts', ['title' => 'T', 'content' => 'C', 'is_published' => true])->assertRedirect('/login');
    $this->patch("/posts/{$post->id}", ['title' => 'T', 'content' => 'C', 'is_published' => true])->assertRedirect('/login');
    $this->delete("/posts/{$post->id}")->assertRedirect('/login');

    expect(Post::count())->toBe(1);
});

test('users can create posts', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Hello',
        'content' => 'World',
        'is_published' => true,
    ]);

    $post = $user->posts()->sole();

    $response->assertSessionHasNoErrors()->assertRedirect("/posts/{$post->id}");
    expect($post->title)->toBe('Hello')->and($post->is_published)->toBeTrue();
});

test('post creation is validated', function () {
    $this->actingAs(User::factory()->create())
        ->post('/posts', ['title' => '', 'content' => '', 'is_published' => 'maybe'])
        ->assertSessionHasErrors(['title', 'content', 'is_published']);
});

test('authors can update their posts', function () {
    $post = Post::factory()->draft()->create();

    $this->actingAs($post->user)
        ->patch("/posts/{$post->id}", ['title' => 'Updated', 'content' => 'Body', 'is_published' => true])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/posts/{$post->id}");

    expect($post->refresh())
        ->title->toBe('Updated')
        ->is_published->toBeTrue();
});

test('users cannot update or delete posts they do not own', function () {
    $post = Post::factory()->create(['title' => 'Original']);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patch("/posts/{$post->id}", ['title' => 'Hacked', 'content' => 'Body', 'is_published' => true])
        ->assertForbidden();
    $this->actingAs($other)->delete("/posts/{$post->id}")->assertForbidden();

    expect($post->refresh()->title)->toBe('Original');
});

test('deleting a post removes its comments and all attachment files', function () {
    Storage::fake('public');

    $post = Post::factory()->create();
    $comment = Comment::factory()->for($post)->create();
    $postFile = Attachment::factory()->for($post, 'attachable')->create();
    $commentFile = Attachment::factory()->for($comment, 'attachable')->create();
    $unrelated = Attachment::factory()->create();

    foreach ([$postFile, $commentFile, $unrelated] as $attachment) {
        Storage::disk('public')->put($attachment->file_path, 'x');
    }

    $this->actingAs($post->user)
        ->delete("/posts/{$post->id}")
        ->assertRedirect('/posts');

    $this->assertModelMissing($post);
    $this->assertModelMissing($comment);
    $this->assertModelMissing($postFile);
    $this->assertModelMissing($commentFile);
    $this->assertModelExists($unrelated);
    Storage::disk('public')->assertMissing([$postFile->file_path, $commentFile->file_path]);
    Storage::disk('public')->assertExists($unrelated->file_path);
});

test('the create page is displayed and is not captured by the show route', function () {
    $this->actingAs(User::factory()->create())
        ->get('/posts/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Posts/Create'));
});

test('users can create posts with images', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post('/posts', [
        'title' => 'With images',
        'content' => 'Body',
        'is_published' => '1',
        'attachments' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.png'),
        ],
    ])->assertSessionHasNoErrors();

    $attachments = $user->posts()->sole()->attachments;

    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('original_name')->all())->toBe(['one.jpg', 'two.png'])
        ->and($attachments->first()->disk)->toBe('public')
        ->and($attachments->first()->mime_type)->toBe('image/jpeg');
    $attachments->each(fn (Attachment $attachment) => Storage::disk('public')->assertExists($attachment->file_path));
});

test('only images are accepted as attachments', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())
        ->post('/posts', [
            'title' => 'T',
            'content' => 'C',
            'is_published' => '1',
            'attachments' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        ])
        ->assertSessionHasErrors('attachments.0');

    expect(Post::count())->toBe(0);
});

test('the edit page is only displayed to the author', function () {
    $post = Post::factory()->has(Attachment::factory(), 'attachments')->create();

    $this->actingAs($post->user)
        ->get("/posts/{$post->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Edit')
            ->where('post.id', $post->id)
            ->has('post.attachments', 1)
        );

    $this->actingAs(User::factory()->create())
        ->get("/posts/{$post->id}/edit")
        ->assertForbidden();
});

test('authors can add new images and remove existing ones when updating', function () {
    Storage::fake('public');

    $post = Post::factory()->create();
    $keep = Attachment::factory()->for($post, 'attachable')->create();
    $remove = Attachment::factory()->for($post, 'attachable')->create();
    Storage::disk('public')->put($keep->file_path, 'x');
    Storage::disk('public')->put($remove->file_path, 'x');

    $this->actingAs($post->user)
        ->patch("/posts/{$post->id}", [
            'title' => 'Updated',
            'content' => 'Body',
            'is_published' => '1',
            'attachments' => [UploadedFile::fake()->image('new.jpg')],
            'remove_attachments' => [$remove->id],
        ])
        ->assertSessionHasNoErrors();

    $this->assertModelMissing($remove);
    Storage::disk('public')->assertMissing($remove->file_path);
    Storage::disk('public')->assertExists($keep->file_path);
    expect($post->attachments()->pluck('original_name')->all())
        ->toEqualCanonicalizing([$keep->original_name, 'new.jpg']);
});

test("authors cannot remove another post's attachments", function () {
    $post = Post::factory()->create();
    $foreign = Attachment::factory()->create();

    $this->actingAs($post->user)
        ->patch("/posts/{$post->id}", [
            'title' => 'T',
            'content' => 'C',
            'is_published' => '1',
            'remove_attachments' => [$foreign->id],
        ])
        ->assertSessionHasErrors('remove_attachments.0');

    $this->assertModelExists($foreign);
});
