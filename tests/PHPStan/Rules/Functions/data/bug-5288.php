<?php declare(strict_types = 1);

namespace Bug5288;

class FooIterator implements \Iterator
{
	/**
	 * @return mixed
	 */
	public function current()
	{
		return '';
	}

	public function next()
	{
	}

	/**
	 * @return scalar|null
	 */
	public function key()
	{
		return '';
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		return true;
	}

	public function rewind()
	{
	}
}

class Test
{
	/** @return FooIterator */
	public function get_iterator() {
		return new FooIterator();
	}

	public function test() {
		$iterator = $this->get_iterator();
		$data = array_map(
			function ($value): void {},
			iterator_to_array($iterator)
		);
	}
}
