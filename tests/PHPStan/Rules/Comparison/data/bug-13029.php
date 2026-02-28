<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13029;

function foo(): void
{
	/** @var bool **/
	$bool1 = true;
	/** @var bool **/
	$bool2 = false;

	$x = match([$bool1, $bool2]) {
		[true, false], [true, true] => 1,
		[false, false] => 0,
		[false, true] => -1,
	};
}
