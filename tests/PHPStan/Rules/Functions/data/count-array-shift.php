<?php

namespace CountArrayShift;

use function PHPStan\Testing\assertType;

/** @param array<mixed>|false $a */
function foo($a): void
{
	while (count($a) > 0) {
		array_shift($a);
	}
}

/** @param non-empty-array<mixed>|false $a */
function bar($a): void
{
	while (count($a) > 0) {
		array_shift($a);
	}
}
