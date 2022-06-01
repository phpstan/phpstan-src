<?php declare(strict_types = 1);

namespace Bug7188;

use Exception;

/**
 * @param int $x
 * @return ($x is 0 ? never : float)
 */
function inverse($x): float
{
	if ($x===0) {
		throw new Exception('Division durch Null.');
	}

	return 1/$x;
}

function () {
	$result = inverse(0);
	echo "expect to unreachable\n";
};
