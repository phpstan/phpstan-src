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
