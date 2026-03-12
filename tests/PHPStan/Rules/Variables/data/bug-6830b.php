<?php

declare(strict_types=1);

namespace Bug6830b;

function test(bool $do): void
{
	if ($do) {
		$x = 9999;
	}

	foreach ([1, 2, 3] as $whatever) {
		if ($do) {
			if ($x) {
				$x = 123;
			}
		}
	}
}
