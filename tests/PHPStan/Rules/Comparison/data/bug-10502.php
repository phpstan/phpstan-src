<?php declare(strict_types=1);

namespace Bug10502;

use ArrayObject;

/** @param ?ArrayObject<int, int> $x */
function doFoo(?ArrayObject $x):void {
	$callable1 = [$x, 'count'];
	$callable2 = array_reverse($callable1, true);

	var_dump(
		is_callable($callable1),
		is_callable($callable2)
	);
}

function doBar():void {
	$callable1 = [new ArrayObject([0]), 'count'];
	$callable2 = array_reverse($callable1, true);

	var_dump(
		is_callable($callable1),
		is_callable($callable2)
	);
}
