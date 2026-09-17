<?php declare(strict_types = 1);

// walk-trace fixture: the implicit index reaches PHP_INT_MAX, the item there
// still gets its Int_ offset node, the item after it an unpredictable offset
function arrayKeyOverflowIncrement(): array
{
	$d = [9223372036854775806 => 1, 2, 3];
	return $d;
}
