<?php

namespace Bug8084;

use function array_shift;
use function PHPStan\Testing\assertType;

class Bug8084
{
	public function run(array $arr): void
	{
		/** @var array{a: 0, b?: 1} $arr */
		assertType('0', array_shift($arr) ?? throw new \Exception());
		assertType('1|null', array_shift($arr));
	}
}
