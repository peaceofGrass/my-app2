<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasReactions;
use App\Support\PostContent;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'content', 'is_published'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasAttachments, HasFactory, HasReactions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Images uploaded from the body editor and embedded in the content.
     *
     * @return HasMany<PostImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }

    /**
     * A plain-text summary of the HTML body for post cards.
     *
     * @return Attribute<string, never>
     */
    protected function excerpt(): Attribute
    {
        return Attribute::get(fn () => PostContent::excerpt($this->content));
    }

    /**
     * Match the editor images to the saved body: attach the author's images it embeds,
     * drop embeds of anyone else's (or unknown) images, and purge images it no longer uses.
     * Call inside the transaction that saves the post.
     */
    public function syncImages(): void
    {
        $images = PostImage::query()
            ->whereKey(PostContent::imageIds($this->content))
            ->where('user_id', $this->user_id)
            ->where(fn ($query) => $query->whereNull('post_id')->orWhere('post_id', $this->id))
            ->lockForUpdate()
            ->get();

        $content = PostContent::bindImages($this->content, $images);

        if ($content !== $this->content) {
            $this->update(['content' => $content]);
        }

        PostImage::whereKey($images->modelKeys())->whereNull('post_id')->update(['post_id' => $this->id]);

        PostImage::purge($this->images()->whereKeyNot($images->modelKeys())->get());
    }
}
