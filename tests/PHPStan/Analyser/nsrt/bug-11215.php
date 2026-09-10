<?php declare(strict_types = 1);

namespace Bug11215;

use function PHPStan\Testing\assertType;

class User {}

/** @template TModel */
class Builder
{
}

/** @template TModel */
class Collection
{
	/** @param callable(Builder<TModel>): mixed $relation */
	public function load($relation): void
	{
		//
	}

	/** @param array<string, (callable(Builder<TModel>): mixed)|string> $relations */
	public function loadMany($relations): void
	{
		//
	}
}

/** @var Collection<User> $users */
$users->load(function ($query) {
	assertType('Bug11215\Builder<Bug11215\User>', $query);
});

$users->loadMany(['foo' => function ($query) {
	assertType('Bug11215\Builder<Bug11215\User>', $query);
}]);
