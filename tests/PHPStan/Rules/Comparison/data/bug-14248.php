<?php

declare(strict_types = 1);

namespace Bug14248Rule;

// the issue's top-level snippet inside a function: by-ref uses are followed
// to where the closure is invoked only in function-like bodies
function doFoo(): void
{
	$a = 0;
	$s = function () use (&$a): int {
		return $a === 0 ? 0 : 1;
	};

	$s();
	$a++;
	$s();
}
