<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed 3-5 posts per user, each with 0-2 attachments.
     */
    public function run(): void
    {
        User::all()->each(function (User $user) {
            Post::factory(fake()->numberBetween(3, 5))
                ->for($user)
                ->create()
                ->each(fn (Post $post) => Attachment::factory(fake()->numberBetween(0, 2))
                    ->for($post, 'attachable')
                    ->create());
        });
    }
}
