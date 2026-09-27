<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15324Match;

function doFoo(string $s): int
{
	return match (substr($s, 0, 2)) {
		'abc' => 1,
		'ab' => 2,
		'a', 'abcd' => 3,
		default => 4,
	};
}

function doBar(string $s): int
{
	return match ($s[0]) {
		'ab' => 1,
		'a' => 2,
		default => 3,
	};
}
