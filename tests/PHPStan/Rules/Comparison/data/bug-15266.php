<?php declare(strict_types = 1);

namespace Bug15266Match;

/** @template T = mixed */
final class X {}

/** @template T = mixed */
final class Y {}

/**
 * @param class-string<X<int>>|class-string<Y<int>> $class
 */
function parametrized(string $class): string
{
	return match ($class) {
		X::class => 'x',
		Y::class => 'y',
	};
}

/**
 * @param class-string<X<*>>|class-string<Y<*>> $class
 */
function star(string $class): string
{
	return match ($class) {
		X::class => 'x',
		Y::class => 'y',
	};
}

/**
 * @param class-string<X>|class-string<Y> $class
 */
function raw(string $class): string
{
	return match ($class) {
		X::class => 'x',
		Y::class => 'y',
	};
}
