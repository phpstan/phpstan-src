<?php declare(strict_types = 1);

namespace Bug7152;

use function PHPStan\Testing\assertType;

/**
 * @template T of array<mixed>
 */
class Root
{
}

/**
 * @phpstan-type Foo array<int>
 * @template T of Foo
 * @extends Root<T>
 */
class Middle extends Root {

	/** @var T */
	public $t;

	public function doFoo(): void
	{
		assertType('T of array<int> (class Bug7152\Middle, argument)', $this->t);
	}

}
