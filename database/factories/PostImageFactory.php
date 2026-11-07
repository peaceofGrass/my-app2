<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostImage>
 */
class PostImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$extension, $mimeType] = fake()->randomElement([
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['gif', 'image/gif'],
            ['webp', 'image/webp'],
        ]);

        return [
            'user_id' => User::factory(),
            'post_id' => null,
            'original_name' => fake()->word().'.'.$extension,
            'file_path' => 'post-images/'.fake()->uuid().'.'.$extension,
            'disk' => 'public',
            'file_size' => fake()->numberBetween(1_000, 5_000_000),
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Attach the image to the given post, uploaded by that post's author.
     */
    public function forPost(Post $post): static
    {
        return $this->state([
            'user_id' => $post->user_id,
            'post_id' => $post->id,
        ]);
    }
}
