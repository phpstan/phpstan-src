<?php

namespace Bug8084;

use Exception;
use function array_shift;
use function PHPStan\Testing\assertType;

class Bug8084
{
	/**
	 * @param array{a?: 0} $arr
	 * @throws Exception
	 */
	public function run(array $arr): void
	{
		assertType('0|null', array_shift($arr) ?? throw new Exception());
	}
}
