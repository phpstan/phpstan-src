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

	/** @var int<0, 2> */
	$int1 = 1;
	/** @var int<0, 2> */
	$int2 = 2;

	$y = match([$int1, $int2]) {
		[0, 0], [0, 1], [0, 2] => 1,
		[1, 1], [1, 2], [1, 0] => 0,
		[2, 1], [2, 0], [2, 2] => -1,
	};

	/** @var 0|1 **/
	$int1 = 1;
	/** @var 0|1 **/
	$int2 = 0;

	$z = match([$int1, $int2]) {
		[1, 0], [1, 1] => 1,
		[0, 0] => 0,
		[0, 1] => -1,
	};
}
