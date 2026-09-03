<?php declare(strict_types = 1);

namespace Bug15168Functions;

/** @template T */
final class Coll
{

	/** @param T|null $t */
	public function __construct($t = null)
	{
	}

}

/**
 * @template T
 * @param T|null $v
 * @return T
 */
function withNull($v)
{
	return $v;
}

/**
 * @template T
 * @param T|false $v
 * @return T
 */
function withFalse($v)
{
	return $v;
}

/**
 * @template T
 * @param T|int|float $v
 * @return T
 */
function withIntAndFloat($v)
{
	return $v;
}

/**
 * @template T
 * @param list<T|null> $v
 * @return list<T>
 */
function fromList(array $v): array
{
	return [];
}

/**
 * @template T
 * @param iterable<T|null> $v
 * @return list<T>
 */
function fromIterable(iterable $v): array
{
	return [];
}

/**
 * @template T
 * @param array{T|null} $v
 * @return T
 */
function fromShape(array $v)
{
	return $v[0];
}

/**
 * @template T
 * @param Coll<T|null> $v
 * @return T
 */
function fromColl(Coll $v)
{
	return $v->t;
}

/**
 * @template T
 * @param callable(): (T|null) $cb
 * @return T
 */
function fromCallable(callable $cb)
{
	return $cb();
}

/**
 * @template T
 * @param T|null ...$v
 * @return T
 */
function variadic(...$v)
{
	return $v[0];
}

/**
 * @template T of object
 * @param class-string<T>|null $c
 * @return T
 */
function fromClassString(?string $c)
{
	return new $c();
}

function test(): void
{
	withNull(null);
	withFalse(false);
	withIntAndFloat(1);
	withIntAndFloat(1.2);
	fromList([null, null]);
	fromIterable([null, null]);
	fromShape([null]);
	fromColl(new Coll(null));
	fromCallable(static fn () => null);
	variadic(null, null);
	fromClassString(null);
}
