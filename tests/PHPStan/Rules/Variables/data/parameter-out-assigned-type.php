<?php

namespace ParameterOutAssignedType;

/**
 * @param-out int $p
 */
function foo(&$p): void {
	$p = 1;
	$p = 'str';
}

class Foo
{

	/**
	 * @param-out int $p
	 */
	public function doFoo(&$p): void {
		$p = 1;
		$p = 'str';
	}

	/**
	 * @param-out string $p
	 */
	function doBar(&$p): void
	{
		$this->doFoo($p);
	}

	/**
	 * @param list<int> $p
	 * @param-out list<int> $p
	 */
	function doBaz(&$p): void
	{
		unset($p[1]);
	}

	/**
	 * @param list<int> $p
	 * @param-out list<int> $p
	 */
	function doBaz2(&$p): void
	{
		$p[] = 'str';
	}

	/**
	 * @param list<list<int>> $p
	 * @param-out list<list<int>> $p
	 */
	function doBaz3(&$p): void
	{
		unset($p[1][2]);
	}

}
