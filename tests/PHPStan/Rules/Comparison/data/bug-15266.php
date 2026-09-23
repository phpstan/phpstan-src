<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug15266Match;

/** @template T = mixed */
final class X {}

/** @template T = mixed */
final class Y {}

/**
 * @param class-string<X<int>> | class-string<Y<int>> $class
 */
function parametrized(string $class): int
{
	return match ($class) {
		X::class => 1,
		Y::class => 2,
	};
}

/**
 * @param class-string<X<*>> | class-string<Y<*>> $class
 */
function star(string $class): int
{
	return match ($class) {
		X::class => 1,
		Y::class => 2,
	};
}

/**
 * @param class-string<X<int>> $class
 */
function single(string $class): int
{
	return match ($class) {
		X::class => 1,
	};
}
