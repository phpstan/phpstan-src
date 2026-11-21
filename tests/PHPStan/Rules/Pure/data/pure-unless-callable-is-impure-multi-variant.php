<?php declare(strict_types = 1);

namespace PureUnlessCallableIsImpureMultiVariant;

/**
 * @param array<int> $a
 * @param array<int> $b
 * @param array<int> $c
 * @param array<int> $d
 * @param array<int> $e
 * @return array<int>
 * @phpstan-pure
 */
function pureCallbackAfterThreeArrays(array $a, array $b, array $c, array $d, array $e): array
{
	$cmp = static fn (int $x, int $y): int => $x <=> $y;

	return array_udiff($a, $b, $cmp)
		+ array_udiff($a, $b, $c, $cmp)
		+ array_udiff($a, $b, $c, $d, $e, $cmp)
		+ array_uintersect_uassoc($a, $b, $c, $cmp, $cmp)
		+ array_diff_uassoc($a, $b, $cmp)
		+ array_diff_uassoc($a, $b, $c, $d, $cmp);
}

/**
 * @param array<int> $a
 * @param array<int> $b
 * @param array<int> $c
 * @param array<int> $d
 * @param array<int> $e
 * @return array<int>
 * @phpstan-pure
 */
function impureCallbackAfterFiveArrays(array $a, array $b, array $c, array $d, array $e): array
{
	return array_udiff($a, $b, $c, $d, $e, static function (int $x, int $y): int {
		echo $x;
		return $x <=> $y;
	});
}
