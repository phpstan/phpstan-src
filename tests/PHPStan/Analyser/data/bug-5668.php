<?php

namespace Bug5668;

use function PHPStan\Testing\assertType;

class Foo
{


	/**
	 * @param array<int, 'test'|'bar'> $in
	 */
	function has(array $in): void
	{
		assertType('bool', in_array('test', $in, true));
	}

	/**
	 * @param array<int, 'test'> $in
	 */
	function has2(array $in): void
	{
		assertType('bool', in_array('test', $in, true));
	}

	/**
	 * @param non-empty-array<int, 'test'|'bar'> $in
	 */
	function has3(array $in): void
	{
		assertType('bool', in_array('test', $in, true));
	}


	/**
	 * @param non-empty-array<int, 'test'> $in
	 */
	function has4(array $in): void
	{
		assertType('true', in_array('test', $in, true));
	}

}
