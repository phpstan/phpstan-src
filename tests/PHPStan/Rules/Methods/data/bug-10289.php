<?php declare(strict_types = 1);

namespace Bug10289;

use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<non-empty-string, non-empty-string>
 */
class X implements IteratorAggregate
{
	/** @var array<non-empty-string, non-empty-string> */
	private array $data = ['x' => 'y'];
	
	/** @return ArrayIterator<non-empty-string, non-empty-string> */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->data);
	}
}
