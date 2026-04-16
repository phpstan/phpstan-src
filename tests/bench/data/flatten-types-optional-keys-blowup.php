<?php declare(strict_types = 1);

namespace BenchFlattenTypesOptionalKeysBlowup;

/**
 * Regression test for TypeUtils::flattenTypes() calling getAllArrays() on arrays with many optional keys.
 * getAllArrays() generates 2^N ConstantArrayType objects for N optional keys.
 * The fix adds a bail-out check and also applies pairwise intersect folding.
 *
 * @param array{
 *     a?: int, b?: int, c?: int, d?: int, e?: int,
 *     f?: int, g?: int, h?: int, i?: int, j?: int,
 *     k?: int, l?: int, m?: int, n?: int, o?: int,
 *     p?: int, q?: int, r?: int
 * } $data
 */
function checkOffset(array $data): void
{
	echo $data['a'];
	echo $data['b'];
	echo $data['c'];
	echo $data['d'];
	echo $data['e'];
}
