<?php declare(strict_types = 1);

// walk-trace fixture: an array literal whose explicit key is PHP_INT_MAX leaves
// the implicit index of the next item unpredictable - AssignHandler's
// processArrayByRefItems() gives that item a TypeExpr(int) offset
function arrayKeyOverflowMax(array $b): array
{
	$a = [PHP_INT_MAX => 1];
	$c = [9223372036854775807 => 1, 2, 3];
	return [$a, $b, $c];
}
