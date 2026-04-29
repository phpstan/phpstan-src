<?php

namespace Bug14549;

use function PHPStan\Testing\assertType;

class MondayMorning
{
	/**
	 * @param callable-array $task
	 */
	public function call(array $task): void
	{
		foreach($task as $k => $v) {
			assertType('0|1', $k);
			assertType('object|string', $v);
		}
		assertType('class-string|object', $task[0]);
		assertType('string', $task[1]);
	}
}


