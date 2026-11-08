<?php

use App\Models\Post;
use Inertia\Testing\AssertableInertia as Assert;

test('index searches published titles and bodies', function () {
    $byTitle = Post::factory()->published()->create(['title' => '라라벨 입문', 'content' => '<p>first</p>', 'created_at' => now()->subDay()]);
    $byBody = Post::factory()->published()->create(['title' => 'Notes', 'content' => '<p>오늘은 <strong>라라벨</strong> 공부</p>']);
    Post::factory()->published()->create(['title' => 'Vue', 'content' => '<p>other</p>']);
    Post::factory()->draft()->create(['title' => '라라벨 초안']);

    $this->get('/posts?q='.urlencode('  라라벨 '))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Index')
            ->where('filters.q', '라라벨')
            ->where('posts.total', 2)
            ->where('posts.data.0.id', $byBody->id)
            ->where('posts.data.1.id', $byTitle->id)
        );
});

test('every search word must match and wildcards match literally', function () {
    $both = Post::factory()->published()->create(['title' => 'Laravel tips', 'content' => '<p>with Vue 100%</p>']);
    Post::factory()->published()->create(['title' => 'Laravel only', 'content' => '<p>a_b</p>']);

    $this->get('/posts?q=vue+laravel')
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 1)->where('posts.data.0.id', $both->id));

    $this->get('/posts?q=%25')
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 1)->where('posts.data.0.id', $both->id));

    $this->get('/posts?q=_')
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 1));
});

test('a search with no matches returns an empty page', function () {
    Post::factory()->published()->create(['title' => 'Hello', 'content' => '<p>World</p>']);

    $this->get('/posts?q=nothing')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', 'nothing')
            ->where('posts.total', 0)
            ->has('posts.data', 0)
        );
});

test('search results keep the query in their pagination links', function () {
    Post::factory(12)->published()->create(['title' => 'match']);

    $this->get('/posts?q=match')
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.total', 12)
            ->where('posts.next_page_url', fn (string $url) => str_contains($url, 'q=match'))
        );
});

test('a blank or malformed search lists every post', function (string $query) {
    Post::factory(2)->published()->create();

    $this->get("/posts?{$query}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.q', '')->where('posts.total', 2));
})->with(['q=', 'q=%20%20', 'q[]=x']);
