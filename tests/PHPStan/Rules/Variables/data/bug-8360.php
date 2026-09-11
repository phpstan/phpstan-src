<?php declare(strict_types = 1);

namespace Bug8360;

function logicalOr(bool $cond, bool $f): void
{
	if ($cond || $f) {
		$x = 1;
	}

	if ($cond && $f) {
		echo $x;
	}
}
