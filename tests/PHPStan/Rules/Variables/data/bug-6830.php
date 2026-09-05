<?php

declare(strict_types=1);

namespace Bug6830;

/** @param array<bool> $bools */
function test(array $bools): void
{
	foreach ($bools as $bool) {
		if ($bool) {
			$foo = 'foo';
		}
		if ($bool) {
			echo $foo;
		}
	}
}

function test2(bool $do): void {

	if ($do) {

		$x = 9999;
	}

	foreach([1, 2, 3] as $whatever) {

		if ($do) {

			if ($x) {

				$x = 123;
			}
		}
	}
}
