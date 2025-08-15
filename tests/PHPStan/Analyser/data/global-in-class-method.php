<?php

class ClassForGlobalTest
{

	public function doSomething(int $count = 3): bool
	{
		global $GLB_A, $GLB_B;

		foreach ([1, 2, 3] as $key => $step) {
			break;
		}

		return false;
	}
}
