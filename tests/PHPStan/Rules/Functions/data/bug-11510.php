<?php

namespace Bug11510;

interface FooInterface {};

class Bar implements FooInterface {
	const CONFIG_TEST = 'test';
};

/**
 * @template T of FooInterface
 * @param class-string<T> $class
 * @param T::CONFIG_* $classConfig
 */
function foo(string $class, string $classConfig): void {
}

foo(Bar::class, 'test');
foo(Bar::class, 'hello');
