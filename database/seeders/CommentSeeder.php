<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * The deepest level of a comment thread (1 = top-level comment).
     */
    private const MAX_DEPTH = 3;

    /**
     * Seed a comment thread up to MAX_DEPTH levels deep on every post.
     */
    public function run(): void
    {
        $users = User::all();

        Post::all()->each(function (Post $post) use ($users) {
            Comment::factory(fake()->numberBetween(2, 4))
                ->for($post)
                ->state(fn () => ['user_id' => $users->random()->id])
                ->create()
                ->each(fn (Comment $comment) => $this->seedReplies($comment, $users, 2));
        });
    }

    /**
     * Recursively create replies to the given comment.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedReplies(Comment $parent, Collection $users, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            return;
        }

        Comment::factory(fake()->numberBetween(0, 2))
            ->replyTo($parent)
            ->state(fn () => ['user_id' => $users->random()->id])
            ->create()
            ->each(fn (Comment $reply) => $this->seedReplies($reply, $users, $depth + 1));
    }
}
