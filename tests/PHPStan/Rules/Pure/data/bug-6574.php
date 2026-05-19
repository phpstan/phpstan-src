<?php declare(strict_types = 1);

namespace Bug6574Pure;

interface FooInterface
{
}

abstract class AbstractBar
{
}

/** @param class-string<FooInterface> $class */
function interfaceWithoutConstructor(string $class): void
{
	new $class();
}

/** @param class-string<AbstractBar> $class */
function abstractClassWithoutConstructor(string $class): void
{
	new $class();
}
