<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasReactions;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['content', 'parent_id'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasAttachments, HasFactory, HasReactions;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Get the ids of every reply nested under this comment, one query per level.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $parentIds = [$this->id];

        while ($parentIds !== []) {
            $parentIds = static::whereIn('parent_id', $parentIds)->pluck('id')->all();
            array_push($ids, ...$parentIds);
        }

        return $ids;
    }
}
