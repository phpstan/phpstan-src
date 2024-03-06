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
