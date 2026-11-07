<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15654;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param list<T> $list
 * @param callable(T): T $fn
 */
function apply(array $list, callable $fn): void {}

apply(['a', 'b'], static function (string $s): string {
	assertType('\'a\'|\'b\'', $s);
	echo match ($s) { 'a' => 1, 'b' => 2 };
	return $s;
});

apply(['a', 'b'], static function (string $s): string {
	assertType('\'a\'|\'b\'', $s);
	echo match ($s) { 'a' => 1, 'b' => 2, default => throw new \LogicException() };
	return $s;
});

apply(['a', 'b'], static function ($s) {
	assertType('\'a\'|\'b\'', $s);
	return $s;
});

apply([], static function (string $s): string {
	assertType('string', $s);
	return $s;
});

/**
 * @template T
 * @param list<T> $list
 * @param callable(T): T $fn
 * @return list<T>
 */
function map(array $list, callable $fn): array
{
	return $list;
}

function (): void {
	assertType('list<\'a\'|\'b\'>', map(['a', 'b'], static fn (string $s): string => $s));
	assertType('list<non-falsy-string>', map(['a', 'b'], static fn (string $s): string => $s . 'x'));
};

/**
 * @template T
 * @param callable(T): T $fn
 * @return T
 */
function only(callable $fn)
{
	return $fn(null);
}

function (): void {
	assertType('string', only(static fn (string $s): string => $s));
	assertType('mixed', only(static fn ($s) => $s));
};

class Collection
{

	/**
	 * @template T
	 * @param list<T> $list
	 * @param callable(T): T $fn
	 * @return list<T>
	 */
	public function map(array $list, callable $fn): array
	{
		return $list;
	}

	/**
	 * @template T
	 * @param list<T> $list
	 * @param callable(T): T $fn
	 * @return list<T>
	 */
	public static function staticMap(array $list, callable $fn): array
	{
		return $list;
	}

}

function (Collection $c): void {
	assertType('list<\'a\'|\'b\'>', $c->map(['a', 'b'], static function (string $s): string {
		assertType('\'a\'|\'b\'', $s);
		return $s;
	}));
	assertType('list<\'a\'|\'b\'>', Collection::staticMap(['a', 'b'], static function (string $s): string {
		assertType('\'a\'|\'b\'', $s);
		return $s;
	}));
};
