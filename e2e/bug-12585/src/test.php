<?php

namespace Bug12585;

use Closure;

use function PHPStan\Testing\assertType;

abstract class Model
{
    final public function __construct()
    {
    }

    /**
     * @template T of Model
     * @param class-string<T> $related
     * @return BelongsTo<T, $this>
     */
    public function belongsTo(string $related): BelongsTo
    {
        return new BelongsTo(); // @phpstan-ignore return.type
    }

    /**
     * @template T of Model
     * @param class-string<T> $related
     * @return HasMany<T, $this>
     */
    public function hasMany(string $related): HasMany
    {
        return new HasMany(); // @phpstan-ignore return.type
    }

    /** @return Builder<static> */
    public static function query(): Builder
    {
        return new Builder(new static());
    }
}

/** @template TModel of Model */
class Builder
{
    /** @param TModel $model */
    final public function __construct(protected Model $model)
    {
    }

    /**
     * @param (\Closure(static): mixed)|string $column
     * @return $this
     */
    public function where(Closure|string $column, mixed $value = null)
    {
        return $this;
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Relation<TRelatedModel, *>|string  $relation
     * @param  (\Closure(Builder<TRelatedModel>): mixed)|null  $callback
     * @return $this
     */
    public function whereHas($relation, ?Closure $callback = null)
    {
        return $this;
    }

    /**
     * @param  string  $relation
     * @param  (\Closure(Builder<*>|Relation<*, *>): mixed)|null  $callback
     * @return $this
     */
    public function withWhereHas($relation, ?Closure $callback = null)
    {
        return $this;
    }
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @mixin Builder<TRelatedModel>
 */
abstract class Relation
{
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @extends Relation<TRelatedModel, TDeclaringModel>
 */
class BelongsTo extends Relation
{
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @extends Relation<TRelatedModel, TDeclaringModel>
 */
class HasMany extends Relation
{
}

final class User extends Model
{
    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

final class Post extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function query(): PostBuilder
    {
        return new PostBuilder(new self());
    }
}

/** @extends Builder<Post> */
class PostBuilder extends Builder
{
}

function test(): void
{
    User::query()->whereHas('posts', function ($query) {
        assertType('Bug12585\PostBuilder', $query);
    });
    User::query()->whereHas('posts', fn (Builder $q) => $q->where('name', 'test'));
    User::query()->whereHas('posts', fn (PostBuilder $q) => $q->where('name', 'test'));

    Post::query()->whereHas('user', fn ($q) => $q->where('name', 'test'));
    Post::query()->withWhereHas('user', function ($query) {
        assertType('Bug12585\BelongsTo<Bug12585\User, Bug12585\Post>|Bug12585\Builder<Bug12585\User>', $query);
    });
    Post::query()->withWhereHas('user', fn (Builder|Relation $q) => $q->where('name', 'test'));
    Post::query()->withWhereHas('user', fn (Builder|BelongsTo $q) => $q->where('name', 'test'));
}
