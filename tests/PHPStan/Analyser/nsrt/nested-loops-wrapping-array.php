<?php declare(strict_types = 1);

namespace NestedLoopsWrappingArray;

use function PHPStan\Testing\assertType;

function run(): void
{
	$x = 0;
	while (rand(0, 1)) {
		$x = [$x];
		while (rand(0, 1)) {
			$x = [$x];
			while (rand(0, 1)) {
				$x = [$x];
				while (rand(0, 1)) {
					$x = [$x];
				}
			}
		}
	}

	assertType('0|array{0|array{0|array{mixed}}}', $x);
}
