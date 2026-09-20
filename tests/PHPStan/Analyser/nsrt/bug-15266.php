<?php declare(strict_types = 1);

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
function genericUnionInsideClassString(string $class): void
{
	if ($class !== X::class) {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}

	echo $class;
}

/**
 * @param class-string<X<int>>|class-string<Y<int>> $class
 */
function identical(string $class): void
{
	if ($class === X::class) {
		assertType('\'Bug15266\\\\X\'', $class);
	} else {
		assertType('class-string<Bug15266\Y<int>>', $class);
	}
}

/**
 * @param class-string<X<int>>|class-string<Y<int>> $class
 */
function switchOnClassString(string $class): void
{
	switch ($class) {
		case X::class:
			assertType('\'Bug15266\\\\X\'', $class);
			break;
		default:
			assertType('class-string<Bug15266\Y<int>>', $class);
	}
}

/**
 * @param class-string<X<int>>|class-string<Y<int>> $class
 */
function inArrayOnClassString(string $class): void
{
	if (in_array($class, [X::class], true)) {
		assertType('\'Bug15266\\\\X\'', $class);
	}
}

/**
 * @param class-string<X<int>>|class-string<Y<int>> $class
 */
function offsetOnClassString(string $class): void
{
	$map = [X::class => 1, Y::class => 2];
	assertType('1|2', $map[$class]);
}

/**
 * @param class-string<X<int>> $class
 */
function unionWithConstantClassString(string $class, bool $bool): void
{
	assertType('class-string<Bug15266\X<int>>', $bool ? $class : X::class);
}

/**
 * @param class-string<X<int>>&literal-string $class
 */
function classStringWithAccessoryType(string $class): void
{
	if ($class !== X::class) {
		assertType('*NEVER*', $class);
	}

	echo $class;
}
