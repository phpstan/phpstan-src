<?php

namespace Bug3997;

use Countable;

class Foo implements Countable
{

	#[\ReturnTypeWillChange]
	public function count()
	{
		return 'foo';
	}

}

class Bar implements Countable
{

	#[\ReturnTypeWillChange]
	public function count(): int
	{
		return 'foo';
	}

}

class Baz implements Countable
{

	/**
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count(): int
	{
		return 'foo';
	}

}

class Lorem implements Countable
{

	/**
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return 'foo';
	}

}

class Ipsum implements Countable
{

	/**
	 * @return string
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return 'foo';
	}

}

class Dolor implements  Countable
{

	/** @return positive-int|0 */
	#[\ReturnTypeWillChange]
	public function count(): int
	{
		return -1;
	}

}
