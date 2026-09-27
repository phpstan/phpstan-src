<?php

namespace StaticVariablesFromUsagesOff;

use function PHPStan\Testing\assertType;

function counter(): int
{
	static $count = 0;
	assertType('mixed', $count);
	$count++;

	return $count;
}
