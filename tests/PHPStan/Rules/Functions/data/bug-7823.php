<?php // lint >= 8.0

namespace Bug7823;

use function PHPStan\Testing\assertType;

/**
 * @param literal-string $s
 */
function sayHello(string $s): void
{
}

class A
{
}

/**
 * @param T $t
 *
 * @template T of A
 */
function x($t): void
{
	assertType('class-string<T of Bug7823\A (function Bug7823\x(), argument)>&literal-string', $t::class);
	sayHello($t::class);
}

/**
 * @param class-string $t
 */
function y($t): void
{
	sayHello($t);
}
