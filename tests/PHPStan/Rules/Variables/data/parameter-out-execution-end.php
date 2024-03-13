<?php

namespace ParameterOutExecutionEnd;

class Foo
{

	/**
	 * @param-out int $p
	 */
	function foo(&$p): void {
		$p = 'str'; // do not report, already reported by ParameterOutAssignedTypeRule
	}

	/**
	 * @param-out string $p
	 */
	function foo2(?string &$p): void {
		// should be reported, type can still be null after execution

		if (rand(0, 1)) {

		} else {

		}
	}

	/**
	 * @param-out string $p
	 */
	function foo3(?string &$p): void {
		// should be reported, type can still be null after execution

		if (rand(0, 1)) {
			$p = 'foo';
		}
	}

	/**
	 * @param-out string $p
	 */
	function foo4(?string &$p): void {
		// should be reported, type can still be null after execution

		if (rand(0, 1)) {
			$p = 'foo';
		} else {

		}
	}

	/**
	 * @param-out string $p
	 */
	function foo5(string &$p): void {
		// should NOT be reported, type is string - valid

		if (rand(0, 1)) {

		} else {

		}
	}

	/**
	 * @param-out int $p
	 */
	function foo6(string &$p): void {
		// should be reported, type is different
	}

}

/**
 * @param-out string $p
 */
function foo2(?string &$p): void {
	// should be reported, type can still be null after execution

	if (rand(0, 1)) {

	} else {

	}
}

class Bug10699
{

	/**
	 * @param int $flags
	 * @param 10|20 $out
	 *
	 * @param-out ($flags is 2 ? 20 : 10) $out
	 */
	function test2(int $flags, int &$out): void
	{
		if ($flags === 2) {
			$out = 20;
			return;
		}


	}

}
