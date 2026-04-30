<?php declare(strict_types = 1);

namespace Bug14560;

/**
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

	yield 'one' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['08:00', '12:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield 'two' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['08:00', '12:00'], ['14:00', '18:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield 'three' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['00:00', '00:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04']],
	];
	yield 'four' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['22:00', '04:00']]]]],
		'lookup' => [],
		'targets' => [[1, '2022-08-04/2022-08-05']],
	];
	yield 'five' => [
		'kind' => $kind,
		'entries' => [[$key => [1, '2022-08-04', [['16:00', '23:00']], 'lookupIds' => [42]]]],
		'lookup' => [42 => [1, '2022-08-05T00:05/2022-08-05T02:00']],
		'targets' => [[1, '2022-08-04/2022-08-05']],
	];
	yield 'six' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-10', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [[1, '2022-08-04'], [1, '2022-08-10']],
	];
	yield 'seven' => [
		'kind' => $kind,
		'entries' => [
			[$key => [1, '2022-08-04', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-05', [['08:00', '12:00']]]],
			[$key => [1, '2022-08-06', [['08:00', '12:00']]]],
		],
		'lookup' => [],
		'targets' => [[1, '2022-08-04/2022-08-06']],
	];
	yield 'eight' => [
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
	yield 'nine' => [
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
