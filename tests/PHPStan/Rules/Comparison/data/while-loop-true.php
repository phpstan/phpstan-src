<?php

namespace WhileLoopTrue;

class Foo
{

	public function doFoo(): void
	{
		while (true) {

		}
	}

	/**
	 * @param 1 $s
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

	public function doBar3(): void
	{
		while (true) {
			if (rand(0, 1)) {
				break;
			}
		}
	}

	public function doBar4(): void
	{
		$b = true;
		while ($b) {
			if (rand(0, 1)) {
				$b = false;
			}
		}
	}

}
