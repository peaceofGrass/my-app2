<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
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
            ['pdf', 'application/pdf'],
            ['zip', 'application/zip'],
        ]);

        return [
            'attachable_type' => Post::class,
            'attachable_id' => Post::factory(),
            'original_name' => fake()->word().'.'.$extension,
            'file_path' => 'attachments/'.fake()->uuid().'.'.$extension,
            'disk' => 'public',
            'file_size' => fake()->numberBetween(1_000, 5_000_000),
            'mime_type' => $mimeType,
        ];
    }
}
