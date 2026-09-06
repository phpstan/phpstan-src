<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12984;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReturnTypeWillChange;

/**
 * @implements ArrayAccess<string, array<string>|bool|int|null>
 * @implements IteratorAggregate<string, array<string>|bool|int|null>
 */
class DocoptResultTest implements ArrayAccess, IteratorAggregate
{

	/** @var array<string, array<string>|bool|int|null> */
	protected array $args;

	/** @param array<string, array<string>|bool|int|null> $args */
	public function __construct(array $args)
	{
		$this->args = $args;
	}

	public function offsetExists($offset): bool
	{
		return key_exists($offset, $this->args);
	}

	/** @return array<string>|bool|int|null */
	#[ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->args[$offset] ?? null;
	}

	public function offsetSet($offset, $value): void
	{
	}

	public function offsetUnset($offset): void
	{
	}

	/** @return ArrayIterator<string, array<string>|bool|int|null> */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->args);
	}

}
