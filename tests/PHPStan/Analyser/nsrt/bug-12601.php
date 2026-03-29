<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12601;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use function PHPStan\Testing\assertType;

/** @implements IteratorAggregate<non-empty-string, non-empty-string> */
class HelloWorld implements IteratorAggregate
{
	/** @param array<non-empty-string, non-empty-string> $map */
	public function __construct(private array $map) {}

	/** @return Traversable<non-empty-string, non-empty-string> */
	public function getIterator(): Traversable
	{
		$iterator = new ArrayIterator($this->map);
		assertType('ArrayIterator<non-empty-string, non-empty-string>', $iterator);
		return $iterator;
	}
}

class HelloWorld3
{
	/** @var ArrayIterator<int, string> */
	private ArrayIterator $a;

	/** @param list<string> $map */
	public function __construct(private array $map) {
		$a = new ArrayIterator($this->map);
		assertType('ArrayIterator<int<0, max>, string>', $a);

		$this->a = $a;
	}
}

/** @implements IteratorAggregate<int<0, max>, non-empty-string> */
class HelloWorld2 implements IteratorAggregate
{
	/** @param array<int<0, max>, non-empty-string> $map */
	public function __construct(private array $map) {}

	/** @return ArrayIterator<int<0, max>, non-empty-string> */
	public function getIterator(): Traversable
	{
		$iterator = new ArrayIterator($this->map);
		assertType('ArrayIterator<int<0, max>, non-empty-string>', $iterator);
		return $iterator;
	}
}
