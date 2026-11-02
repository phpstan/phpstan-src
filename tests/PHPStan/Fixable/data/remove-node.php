<?php

namespace RemoveNode;

class Foo
{

	const REMOVE_A = 1, REMOVE_B = 2;

	const KEEP = 1, REMOVE_C = 2;

	public $removeA, $removeB;

	public $keep, $removeC;

	public function doFoo(): void
	{
		echo 1;
		removeMe();
		echo 2;
		sprintf('%s', REMOVE_ARG, 'a');
		if (removeMe()) {
			echo 3;
		}
	}

	/**
	 * Removed together with its PHPDoc.
	 */
	public function removeMe(): void
	{
	}

	public function doBar(): void
	{
	}

}
