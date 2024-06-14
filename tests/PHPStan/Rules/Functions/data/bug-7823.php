<?php // onlyif PHP_VERSION_ID >= 80000

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

/**
 * @param Z $t
 *
 * @template Z
 */
function z($t): void
{
	assertType('class-string<Z (function Bug7823\z(), argument)>&literal-string', $t::class);
	sayHello($t::class);
}

/**
 * @param object $o
 */
function a($o): void
{
	assertType('class-string&literal-string', $o::class);
	sayHello($o::class);
}
