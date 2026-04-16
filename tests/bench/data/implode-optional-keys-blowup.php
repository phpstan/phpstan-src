<?php declare(strict_types = 1);

namespace BenchImplodeOptionalKeysBlowup;

/**
 * Regression test for implode() calling getAllArrays() on arrays with many optional keys.
 * getAllArrays() generates 2^N ConstantArrayType objects for N optional keys.
 * The fix processes keys incrementally, bailing early when partial count exceeds the limit.
 *
 * @param array{
 *     a?: 'x', b?: 'x', c?: 'x', d?: 'x', e?: 'x',
 *     f?: 'x', g?: 'x', h?: 'x', i?: 'x', j?: 'x',
 *     k?: 'x', l?: 'x', m?: 'x', n?: 'x', o?: 'x',
 *     p?: 'x', q?: 'x', r?: 'x', s?: 'x', t?: 'x'
 * } $data
 */
function joinOptional(array $data): string
{
	return implode(',', $data);
}
