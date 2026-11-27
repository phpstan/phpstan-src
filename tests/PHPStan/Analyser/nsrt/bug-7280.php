<?php declare(strict_types = 1);

namespace Bug7280;

use function PHPStan\Testing\assertType;

$result = array_reduce(
	['test1', 'test2'],
	static function (array $carry, string $value): array{
		assertType("array{starts: array{}, ends: array{}}|array{starts: non-empty-list<'test1'|'test2'>, ends: non-empty-list<'test1'|'test2'>}", $carry);
		$carry['starts'][] = $value;
		$carry['ends'][] = $value;

		return $carry;
	},
	initial: ['starts' => [], 'ends' => []],
);
assertType("array{starts: non-empty-list<'test1'|'test2'>, ends: non-empty-list<'test1'|'test2'>}", $result);
