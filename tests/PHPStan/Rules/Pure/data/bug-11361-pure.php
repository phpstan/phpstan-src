<?php

namespace Bug11361Pure;

/**
 * @phpstan-pure
 */
function foo(): int
{
	$bar = function(&$var) {
		$var++;
	};
	$a = array(0);
	$bar($a[0]);

	return 1;
}
