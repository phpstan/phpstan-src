<?php

namespace RemoveNode;

class Foo
{

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
