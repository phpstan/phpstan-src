<?php

namespace Bug11510Nsrt;

use function PHPStan\Testing\assertType;

interface FooInterface {}

class Bar implements FooInterface
{
	const CONFIG_TEST = 'test';
}

/**
 * @template T of FooInterface
 * @param class-string<T> $class
 * @param T::CONFIG_* $classConfig
 */
function foo(string $class, string $classConfig): void
{
	// T is any implementation of FooInterface - only the native type is certain
	assertType('string', $classConfig);
}

/**
 * @template T of FooInterface
 * @param class-string<T> $class
 * @return T::CONFIG_*
 */
function config(string $class): string
{
	return '';
}

function (): void {
	assertType("'test'", config(Bar::class));
};
