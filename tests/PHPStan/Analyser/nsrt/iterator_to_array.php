<?php

namespace IteratorToArray;

use Traversable;
use function iterator_to_array;
use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param Traversable<string, int> $foo
	 */
	public function testDefaultBehavior(Traversable $foo)
	{
		assertType('array<string, int>', iterator_to_array($foo));
	}

	/**
	 * @param Traversable<string, string> $foo
	 */
	public function testExplicitlyPreserveKeys(Traversable $foo)
	{
		assertType('array<string, string>', iterator_to_array($foo, true));
	}

	/**
	 * @param Traversable<string, string> $foo
	 */
	public function testNotPreservingKeys(Traversable $foo)
	{
		assertType('list<string>', iterator_to_array($foo, false));
	}
}
