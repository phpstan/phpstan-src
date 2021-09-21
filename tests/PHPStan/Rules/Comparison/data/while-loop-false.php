<?php

namespace WhileLoopFalse;

class Foo
{

	public function doFoo(): void
	{
		while (false) {

		}
	}

	/**
	 * @param 0 $s
	 */
	public function doBar($s): void
	{
		while ($s) {

		}
	}

	/**
	 * @param string $s
	 */
	public function doBar2($s): void
	{
		while ($s === null) { // reported by StrictComparisonOfDifferentTypesRule

		}
	}

}
