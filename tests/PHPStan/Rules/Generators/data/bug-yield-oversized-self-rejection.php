<?php declare(strict_types = 1);

namespace BugYieldOversizedSelfRejection;

/**
 * Reproducer for a regression where `optimizeConstantArrays` produced a
 * Generator value type that did not accept the very yields it was inferred
 * from. Each yield is well-typed; the closure's inferred Generator value
 * type must therefore be a super-type of every value it actually yields.
 *
 * @param callable(string): iterable<string, mixed> $fn
 * @return \Generator<string, mixed>
 */
function bridge(callable $fn): \Generator
{
	foreach (['a', 'b'] as $kind) {
		yield from $fn($kind);
	}
}

bridge(static function (string $kind): iterable {
	$key = 'b' === $kind ? 'x' : 'y';

	yield '1' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['08:00', '12:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield '2' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['08:00', '12:00'], ['14:00', '18:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield '3' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['00:00', '00:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield '4' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['22:00', '04:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04/2022-08-05']],
	];
	yield '5' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['16:00', '23:00']], 'lookupIds' => [42]]]],
		'lookup' => [42 => [1, '2022-08-05T00:05/2022-08-05T02:00']],
		'targets' => [[1, '2022-08-04/2022-08-05']],
	];
	yield '6' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-10', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [[1, '2022-08-04'], [1, '2022-08-10']],
	];
	yield '7' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-05', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-06', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [[1, '2022-08-04/2022-08-06']],
	];
	yield '8' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [2, '2022-08-05', [['08:00', '12:00']]]],
			[$key => [2, '2022-08-06', [['08:00', '12:00']]]],
			[$key => [3, '2022-08-06', [['08:00', '12:00']]]],
			[$key => [3, '2022-08-10', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [[1, '2022-08-04'], [2, '2022-08-05/2022-08-06'], [3, '2022-08-06'], [3, '2022-08-10']],
	];
	yield '9' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-05', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [],
		'flag' => false,
	];
});
