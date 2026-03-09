<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug7280;

use function PHPStan\Testing\assertType;

// Test 1: Precise carry type from initial argument with constant array shape
$result1 = array_reduce(
	['test1', 'test2'],
	static function (array $carry, string $value): array {
		assertType("array{starts: array{}, ends: array{}}", $carry);
		$carry['starts'][] = $value;
		$carry['ends'][] = $value;

		return $carry;
	},
	initial: ['starts' => [], 'ends' => []],
);

// Test 2: Arrow function with precise carry type
$result2 = array_reduce(
	[1, 2, 3],
	static fn (int $carry, int $value): int => $carry + $value,
	0,
);
assertType('int', $result2);

// Test 3: Carry type with no initial (defaults to null)
$result3 = array_reduce(
	[1, 2, 3],
	static function (?int $carry, int $value): int {
		assertType('null', $carry);
		return ($carry ?? 0) + $value;
	},
);

// Test 4: Initial value type narrows carry - literal string initial
$result4 = array_reduce(
	['a', 'b', 'c'],
	static function (string $carry, string $value): string {
		assertType('string', $carry);
		return $carry . $value;
	},
	'',
);
assertType('string', $result4);

// Test 5: Carry type with literal int initial
$result5 = array_reduce(
	[1, 2, 3],
	static function (int $carry, int $value): int {
		assertType('int', $carry);
		return $carry + $value;
	},
	0,
);
assertType('int', $result5);
