<?php declare(strict_types = 1);

namespace ArrayCallableDeps;

/**
 * The first item is only maybe a class-string: no callable dependency.
 *
 * @param class-string<Factory>|int $classOrInt
 */
function maybeClassString($classOrInt): void
{
	$cb = [$classOrInt, 'create'];
}

/**
 * The first item is only maybe a class-string: no callable dependency.
 *
 * @param class-string<OtherFactory>|OtherFactory $classOrObject
 */
function maybeClassStringOrObject($classOrObject): void
{
	$cb = [$classOrObject, 'create'];
}

/**
 * An object-first array is not a `[Foo::class, 'method']` callable: no
 * callable dependency.
 */
function objectFirst(Factory $factory): void
{
	$cb = [$factory, 'create'];
}
