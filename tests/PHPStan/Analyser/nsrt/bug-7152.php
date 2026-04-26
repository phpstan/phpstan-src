<?php declare(strict_types = 1);

namespace Bug7152;

use function PHPStan\Testing\assertType;

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

function () {
	/** @var Middle<array<int>> $m */
	$m = new Middle();
	assertType('array<int>', $m->value);

	/** @var Middle2<array<int>> $m2 */
	$m2 = new Middle2();
	assertType('array<int>', $m2->value);
};
