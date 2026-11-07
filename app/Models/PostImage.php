<?php

namespace App\Models;

use Database\Factories\PostImageFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * An image uploaded from the post body editor and embedded in the post's HTML content.
 * Kept separate from Attachment, which holds the post/comment gallery images.
 */
#[Fillable(['original_name', 'file_path', 'disk', 'file_size', 'mime_type'])]
#[Appends(['url'])]
class PostImage extends Model
{
    /** @use HasFactory<PostImageFactory> */
    use HasFactory, Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * The user who uploaded the image.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The post the image belongs to, or null while the post has not been saved yet.
     *
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Images not attached to any post yet.
     *
     * @param  Builder<PostImage>  $query
     */
    public function scopeUnattached(Builder $query): void
    {
        $query->whereNull('post_id');
    }

    /**
     * Uploads that never made it into a saved post within a day (abandoned or removed while writing).
     *
     * @return Builder<PostImage>
     */
    public function prunable(): Builder
    {
        return static::unattached()->where('created_at', '<=', now()->subDay());
    }

    /**
     * Delete the file before `model:prune` deletes the row.
     */
    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->file_path);
    }

    /**
     * The public URL of the file on its storage disk.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk($this->disk)->url($this->file_path));
    }

    /**
     * Delete the image rows now and their files once the surrounding transaction commits.
     * The foreign key cascade only removes rows, so call this before deleting a post
     * to keep its files from being left behind on the disk.
     *
     * @param  Collection<int, PostImage>  $images
     */
    public static function purge(Collection $images): void
    {
        if ($images->isEmpty()) {
            return;
        }

        static::whereKey($images->modelKeys())->delete();

        DB::afterCommit(fn () => $images->each(
            fn (PostImage $image) => Storage::disk($image->disk)->delete($image->file_path),
        ));
    }
}
