<?php

namespace Bug7823;

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
	sayHello($t::class);
}

/**
 * @param class-string $t
 */
function y($t): void
{
	sayHello($t);
}
