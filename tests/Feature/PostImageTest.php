<?php

use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('an editor image can be uploaded before its post exists', function () {
    $image = PostImage::factory()->create();

    expect($image->post_id)->toBeNull()
        ->and(PostImage::unattached()->pluck('id')->all())->toBe([$image->id]);
});

test('a post lists its editor images', function () {
    $post = Post::factory()->create();
    $images = PostImage::factory()->count(2)->forPost($post)->create();

    expect($post->images->modelKeys())->toEqualCanonicalizing($images->modelKeys())
        ->and($images->first()->user->is($post->user))->toBeTrue();
});

test('deleting a post cascades to its editor images', function () {
    $post = Post::factory()->create();
    PostImage::factory()->count(2)->forPost($post)->create();
    $unattached = PostImage::factory()->create();

    $post->delete();

    expect(PostImage::pluck('id')->all())->toBe([$unattached->id]);
});

test('deleting a user cascades to the images they uploaded', function () {
    $user = User::factory()->create();
    PostImage::factory()->for($user)->create();

    $user->delete();

    expect(PostImage::count())->toBe(0);
});

test('purge deletes the rows and their files', function () {
    Storage::fake('public');
    $image = PostImage::factory()->create();
    Storage::disk('public')->put($image->file_path, 'image');

    PostImage::purge(PostImage::all());

    expect(PostImage::count())->toBe(0);
    Storage::disk('public')->assertMissing($image->file_path);
});

test('guests cannot upload editor images and get a json 401', function () {
    $this->post('/post-images', ['image' => UploadedFile::fake()->image('a.png')])->assertUnauthorized();

    expect(PostImage::count())->toBe(0);
});

test('users can upload editor images', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/post-images', [
        'image' => UploadedFile::fake()->image('photo.png'),
    ]);

    $image = PostImage::sole();

    $response->assertCreated()->assertExactJson(['id' => $image->id, 'url' => $image->url]);
    expect($image)
        ->user_id->toBe($user->id)
        ->post_id->toBeNull()
        ->original_name->toBe('photo.png');
    Storage::disk('public')->assertExists($image->file_path);
});

test('editor image uploads must be images', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())
        ->postJson('/post-images', ['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(PostImage::count())->toBe(0);
});

test('saving a post attaches the embedded images and drops images it may not use', function () {
    $user = User::factory()->create();
    $mine = PostImage::factory()->for($user)->create();
    $someoneElses = PostImage::factory()->create();
    $otherPost = PostImage::factory()->forPost(Post::factory()->for($user)->create())->create();

    $this->actingAs($user)->post('/posts', [
        'title' => 'Hello',
        'content' => '<p>Hi</p>'
            .'<img src="https://evil.test/x.png" data-image-id="'.$mine->id.'">'
            .'<img src="/x.png" data-image-id="'.$someoneElses->id.'">'
            .'<img src="/y.png" data-image-id="'.$otherPost->id.'">'
            .'<img src="https://tracker.test/pixel.gif">',
        'is_published' => true,
    ])->assertSessionHasNoErrors();

    $post = $user->posts()->latest('id')->first();

    expect($post->content)->toBe('<p>Hi</p><img src="'.$mine->url.'" data-image-id="'.$mine->id.'">')
        ->and($post->images->modelKeys())->toBe([$mine->id])
        ->and($someoneElses->refresh()->post_id)->toBeNull()
        ->and($otherPost->refresh()->post_id)->not->toBe($post->id);
});

test('updating a post purges the editor images it no longer embeds', function () {
    Storage::fake('public');
    $post = Post::factory()->create();
    [$kept, $removed] = PostImage::factory()->count(2)->forPost($post)->create()->all();
    Storage::disk('public')->put($kept->file_path, 'image');
    Storage::disk('public')->put($removed->file_path, 'image');

    $this->actingAs($post->user)->patch("/posts/{$post->id}", [
        'title' => 'Updated',
        'content' => '<p>Body</p><img src="'.$kept->url.'" data-image-id="'.$kept->id.'">',
        'is_published' => true,
    ])->assertSessionHasNoErrors();

    expect($post->images()->pluck('id')->all())->toBe([$kept->id])
        ->and(PostImage::find($removed->id))->toBeNull();
    Storage::disk('public')->assertExists($kept->file_path);
    Storage::disk('public')->assertMissing($removed->file_path);
});

test('deleting a post deletes its editor image files', function () {
    Storage::fake('public');
    $post = Post::factory()->create();
    $image = PostImage::factory()->forPost($post)->create();
    Storage::disk('public')->put($image->file_path, 'image');

    $this->actingAs($post->user)->delete("/posts/{$post->id}")->assertRedirect('/posts');

    expect(PostImage::count())->toBe(0);
    Storage::disk('public')->assertMissing($image->file_path);
});

test('post bodies are sanitized before they are stored', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/posts', [
        'title' => 'Hello',
        'content' => '<p onclick="steal()">Hi<script>steal()</script> <a href="javascript:steal()">x</a></p>',
        'is_published' => true,
    ])->assertSessionHasNoErrors();

    expect($user->posts()->sole()->content)
        ->toBe('<p>Hi <a rel="noopener noreferrer nofollow" target="_blank">x</a></p>');
});

test('an empty editor body is rejected', function (string $content) {
    $this->actingAs(User::factory()->create())
        ->post('/posts', ['title' => 'Hello', 'content' => $content, 'is_published' => true])
        ->assertSessionHasErrors('content');
})->with(['<p></p>', '<p>&nbsp;</p>', '<p><script>x</script></p>']);

test('the post list sends a plain-text excerpt instead of the body', function () {
    Post::factory()->published()->create(['content' => '<h2>Title</h2><p>Body &amp; more</p>']);

    $this->get('/posts')->assertInertia(fn (Assert $page) => $page
        ->where('posts.data.0.excerpt', 'Title Body & more')
        ->missing('posts.data.0.content'));
});

test('stale unattached editor images are pruned with their files', function () {
    Storage::fake('public');
    $stale = PostImage::factory()->create(['created_at' => now()->subDays(2)]);
    $fresh = PostImage::factory()->create();
    $attached = PostImage::factory()->forPost(Post::factory()->create())->create(['created_at' => now()->subDays(2)]);
    Storage::disk('public')->put($stale->file_path, 'image');

    $this->artisan('model:prune', ['--model' => PostImage::class])->assertSuccessful();

    expect(PostImage::pluck('id')->all())->toEqualCanonicalizing([$fresh->id, $attached->id]);
    Storage::disk('public')->assertMissing($stale->file_path);
});
