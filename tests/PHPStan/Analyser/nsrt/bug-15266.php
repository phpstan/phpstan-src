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
