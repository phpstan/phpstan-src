<?php declare(strict_types = 1);

namespace BenchFiniteTypesOptionalKeysCount;

/**
 * Regression test for ConstantArrayType::getFiniteTypes() building partial arrays it then threw away.
 *
 * Every strict comparison of the two shapes asks both of them for their finite types. Each
 * optional key doubles the partial arrays and each bool value doubles them again, so the
 * CALCULATE_SCALARS_LIMIT is exceeded after a handful of keys - but the builder was cloned for
 * every partial before the count was checked, hundreds of clones per call for an empty result.
 * The per-key counts are now multiplied first.
 *
 * `bin/phpstan analyse -l 8 --debug` on this file: 2.8 s on 2.3.x, 1.35 s with the fix.
 *
 * @param array{k1?: bool, k2?: bool, k3?: bool, k4?: bool, k5?: bool, k6?: bool, k7?: bool, k8?: bool} $x
 * @param array{k1?: bool, k2?: bool, k3?: bool, k4?: bool, k5?: bool, k6?: bool, k7?: bool, k8?: bool} $y
 */
function compare(array $x, array $y): void
{
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
	if ($x === $y) {
		echo 1;
	}
}
