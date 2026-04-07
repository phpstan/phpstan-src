<?php

namespace BugConstantArrayCallableRecursion;

class Period
{

	/**
	 * @return array{'BugConstantArrayCallableRecursion\Period', 'endIteration'}
	 */
	public function endIteration(): callable
	{
		return [self::class, 'endIteration'];
	}

}

function test(): void
{
	$cb = ['BugConstantArrayCallableRecursion\Period', 'endIteration'];
	is_callable($cb);
}
