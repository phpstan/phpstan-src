<?php declare(strict_types = 1);

namespace Bug15303;

use function PHPStan\Testing\assertType;

class User { public int $id = 1; }

/**
 * @param array<mixed> $a
 * @param mixed $cb
 * @return array<mixed>
 */
function keyBy(array $a, $cb): array { return $a; }

/**
 * @param array<mixed> $a
 * @param mixed $cb
 * @return array<mixed>
 */
function groupBy(array $a, $cb): array { return $a; }

class Collection
{

	/**
	 * @param mixed $cb
	 * @return array<mixed>
	 */
	public function keyBy($cb): array { return []; }

}

/** @param array<int, User> $users */
function test(array $users, Collection $collection): void {
	assertType('array<int, Bug15303\User>', keyBy($users, fn ($u) => $u->id));
	assertType('array<int, Bug15303\User>', keyBy($users, function ($u) { return $u->id; }));
	assertType('array<int, Bug15303\User>', groupBy($users, ['name', fn ($u) => $u->id]));
	assertType('array<int, Bug15303\User>', groupBy($users, ['name', function ($u) { return $u->id; }]));
	assertType('array<int, Bug15303\User>', $collection->keyBy(fn ($u) => $u->id));
	assertType('array<int, Bug15303\User>', $collection->keyBy(function ($u) { return $u->id; }));
}

/** @template T of User */
class Box
{

	/** @param array<int, T> $items */
	public function test(array $items): void
	{
		assertType('array<int, T of Bug15303\User (class Bug15303\Box, argument)>', keyBy($items, fn ($u) => $u->id));
		assertType('array<int, T of Bug15303\User (class Bug15303\Box, argument)>', keyBy($items, function ($u) { return $u->id; }));
	}

}
