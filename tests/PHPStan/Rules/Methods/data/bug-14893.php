<?php

namespace Bug14893;

/**
 * @template T of object
 */
class HelloWorld
{
	/**
	 * @param int $offset
	 * @param \Closure(static): T $value
	 */
	public function offsetSet($offset, \Closure $value): void
	{
	}

	/**
	 * @param (\Closure(static): T)|(\Closure&T) $value
	 */
	public function foo($value): void
	{
		$this->offsetSet(0, $value);
	}

	/**
	 * @param (\Closure(static): T)|(\Closure&T) $value
	 * @return \Closure(static): T
	 */
	public function bar($value): \Closure
	{
		return $value;
	}
}
