<?php declare(strict_types = 1);

namespace NestedLoopCounter;

use function PHPStan\Testing\assertType;

function forInFor(int $max): void
{
	for ($d = 0; $d <= $max; $d++) {
		for ($k = -$d; $k <= $d; $k += 2) {
			assertType('int', $k);
		}
	}
}

function forInWhile(int $max): void
{
	$d = 0;
	while ($d < $max) {
		for ($k = 0; $k <= $d; $k += 2) {
			assertType('0|int<2, max>', $k);
		}
		$d++;
	}
}
