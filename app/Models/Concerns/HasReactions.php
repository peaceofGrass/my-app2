<?php

namespace App\Models\Concerns;

use App\Enums\ReactionType;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\UniqueConstraintViolationException;

trait HasReactions
{
    /**
     * @return MorphMany<Reaction, $this>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Apply the user's reaction: a different type replaces the current one, the same type again removes it.
     */
    public function toggleReaction(User $user, ReactionType $type): void
    {
        $existing = $this->reactions()->whereBelongsTo($user)->first();

        if ($existing?->type === $type) {
            $existing->delete();
        } elseif ($existing) {
            $existing->update(['type' => $type]);
        } else {
            try {
                $this->reactions()->make(['type' => $type])->user()->associate($user)->save();
            } catch (UniqueConstraintViolationException) {
                // A concurrent request (e.g. a double click) already recorded this user's reaction.
            }
        }
    }

    /**
     * Add likes_count, dislikes_count, and the given user's my_reaction to each result.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithReactionSummary(Builder $query, ?User $user): void
    {
        $query->withCount(static::reactionCounts())->addSelect([
            'my_reaction' => Reaction::query()
                ->select('type')
                ->whereColumn('reactable_id', $this->getQualifiedKeyName())
                ->where('reactable_type', $this->getMorphClass())
                ->where('user_id', $user?->id)
                ->limit(1),
        ]);
    }

    /**
     * Load likes_count, dislikes_count, and the given user's my_reaction onto this model.
     */
    public function loadReactionSummary(?User $user): static
    {
        $this->loadCount(static::reactionCounts());

        $this->setAttribute('my_reaction', $user
            ? $this->reactions()->whereBelongsTo($user)->first()?->type->value
            : null);

        return $this;
    }

    /**
     * @return array<string, callable(Builder<Reaction>): mixed>
     */
    private static function reactionCounts(): array
    {
        return [
            'reactions as likes_count' => fn (Builder $query) => $query->where('type', ReactionType::Like),
            'reactions as dislikes_count' => fn (Builder $query) => $query->where('type', ReactionType::Dislike),
        ];
    }
}
