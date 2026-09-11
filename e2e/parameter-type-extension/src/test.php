<?php declare(strict_types = 1);

namespace App;

use function PHPStan\Testing\assertType;

abstract class Model {}

class Monitor extends Model {}

class Car extends Model {}

class User extends Model
{
	/** @return HasOne<Car, $this> */
	public function car(): HasOne
	{
		return new HasOne(); // @phpstan-ignore return.type
	}

	/** @return MorphTo<Monitor, $this> */
	public function monitorable(): MorphTo
	{
		return new MorphTo(); // @phpstan-ignore return.type
	}
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @template TResult
 */
class Relation {
	/**
	 * @param list<string> $columns
	 * @return $this
	 */
	public function select(array $columns): static
	{
		return $this;
	}
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @extends Relation<TRelatedModel, TDeclaringModel, ?TRelatedModel>
 */
class HasOne extends Relation {}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 * @extends Relation<TRelatedModel, TDeclaringModel, ?TRelatedModel>
 */
class MorphTo extends Relation {
	/** @return $this */
	public function morphWith(): static
	{
		return $this;
	}
}

/** @template TModel of Model */
class Builder
{
	/**
	 * @param  array<string, \Closure(Relation<*, *, *>): mixed>  $relations
	 * @return $this
	 */
	public function with(array $relations): static
	{
		return $this;
	}
}

/** @param Builder<User> $query */
function test(Builder $query): void
{
	$query->with([
		'car' => function ($r) { assertType('App\HasOne<App\Car, App\User>', $r); },
		'monitorable' => function ($r) { assertType('App\MorphTo<App\Monitor, App\User>', $r); },
	]);
	$query->with([
		'car' => fn (HasOne $q) => $q->select(['id']),
		'monitorable' => fn (MorphTo $q) => $q->morphWith(),
	]);
}
