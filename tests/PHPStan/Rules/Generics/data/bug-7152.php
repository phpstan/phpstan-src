<?php declare(strict_types = 1);

namespace Bug7152Generics;

/**
 * @template T of array<mixed>
 */
class Root
{
	/** @var T */
	public array $value;
}

/**
 * @phpstan-type Foo array<int>
 * @template T of Foo
 * @extends Root<T>
 */
class Middle extends Root
{
}

/**
 * @template T of array<int>
 * @extends Root<T>
 */
class Middle2 extends Root
{
}
