<?php // lint >= 8.1

namespace Bug9699;

function withVariadicParam(int $a, int $b, int ...$rest): int
{
	return array_sum([$a, $b, ...$rest]);
}

/**
 * @param \Closure(int, int, int, string): int $f
 */
function int_int_int_string(\Closure $f): void
{
	$f(0, 0, 0, '');
}

// false negative: expected issue here
int_int_int_string(withVariadicParam(...));
