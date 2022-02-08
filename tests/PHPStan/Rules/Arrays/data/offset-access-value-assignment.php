<?php

namespace OffsetAccessValueAssignment;

class Foo
{

	/**
	 * @param \ArrayAccess<int,int> $arrayAccess
	 */
	public function doFoo(\ArrayAccess $arrayAccess): void
	{
		$arrayAccess[] = 'foo';
		$arrayAccess[] = 1;
		$arrayAccess[2] = 'bar';

		$i = 1;
		$arrayAccess[] = $i;

		$arrayAccess[] = 'baz';
		$arrayAccess[] = ['foo'];

		$s = 'foo';
		$arrayAccess[] = &$s;
	}

	public function doBar(int $test): void
	{
		$test[2] = 'foo';
	}

	/**
	 * @param \ArrayAccess<int,int> $arrayAccess
	 */
	public function doBaz(\ArrayAccess $arrayAccess): void
	{
		$arrayAccess[1] += 1;
		$arrayAccess[1] += 2.5;
	}

	public function doLorem(string $str): void
	{
		$str[3] = 'bar';
	}

}

class AppendToArrayAccess
{
	/** @var \ArrayAccess<int, string> */
	private $collection1;

	/** @var \ArrayAccess<int, string>&\Countable */
	private $collection2;

	public function foo(): void
	{
		$this->collection1[] = 1;
		$this->collection2[] = 2;
	}
}
