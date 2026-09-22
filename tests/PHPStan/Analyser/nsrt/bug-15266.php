<?php declare(strict_types=1);

namespace Bug15266;

use function PHPStan\Testing\assertType;

/** @template T = mixed */
final class X {}

/** @template T = mixed */
final class Y {}

/**
 * @param class-string<X<int>> | class-string<Y<int>> $class
 * @return class-string<X<int>> | class-string<Y<int>>
 */
function parametrized(string $class): string
{
	if ($class !== X::class) {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}

	return $class;
}

/**
 * @param class-string<X<*>> | class-string<Y<*>> $class
 * @return class-string<X<*>> | class-string<Y<*>>
 */
function star(string $class): string
{
	if ($class !== X::class) {
		assertType('class-string<Bug15266\Y<*>>', $class);
	}

	return $class;
}

/**
 * @param class-string<X> | class-string<Y> $class
 * @return class-string<X> | class-string<Y>
 */
function raw(string $class): string
{
	if ($class !== X::class) {
		assertType('class-string<Bug15266\Y>', $class);
	}

	return $class;
}

/**
 * @param class-string<X<int>|Y<int>> $class
 */
function genericUnion(string $class): void
{
	if ($class !== X::class) {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}
}

/**
 * @template T of X<int>|Y<int>
 * @param class-string<T> $class
 */
function templateBound(string $class): void
{
	if ($class !== X::class) {
		assertType('class-string<T of Bug15266\Y<int> (function Bug15266\templateBound(), argument)>', $class);
	}
}

/**
 * @param class-string<X<int>> | class-string<Y<int>> $class
 */
function inArray(string $class): void
{
	if (in_array($class, [X::class], true)) {
		assertType('\'Bug15266\\\\X\'&class-string<Bug15266\X<int>>', $class);
	} else {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}
}

/**
 * @param class-string<X<int>> | class-string<Y<int>> $class
 */
function identical(string $class): void
{
	if ($class === X::class) {
		assertType('\'Bug15266\\\\X\'&class-string<Bug15266\X<int>>', $class);
	} else {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}
}
