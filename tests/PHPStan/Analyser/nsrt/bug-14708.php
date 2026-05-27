<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14708;

use function PHPStan\Testing\assertType;

/** @return array{test: bool, spread?: true} */
function test1(bool $spread): array {
	$result = [
		'test' => $spread,
		...($spread ? ['spread' => true] : []),
	];
	assertType('array{test: bool, spread?: true}', $result);
	return $result;
}

/** @return array{test: bool, spread?: true} */
function test2(bool $spread): array {
	$return1 = ['test' => $spread];
	$return2 = $spread ? ['spread' => true] : [];

	$result = [...$return1, ...$return2];
	assertType('array{test: bool, spread?: true}', $result);
	return $result;
}

/** @return array{test: bool, spread?: true} */
function test3(bool $spread): array {
	$return = ['test' => $spread];
	if ($spread) {
		$return['spread'] = true;
	}

	assertType('array{test: bool, spread?: true}', $return);
	return $return;
}

function testMultipleOptionalKeys(bool $a, bool $b): void {
	$result = [
		'base' => 1,
		...($a ? ['x' => 'hello'] : []),
		...($b ? ['y' => 42] : []),
	];
	assertType("array{base: 1, x?: 'hello', y?: 42}", $result);
}

function testOverlappingKeys(bool $flag): void {
	$result = [
		'a' => 1,
		...($flag ? ['a' => 2, 'b' => 3] : ['b' => 4]),
	];
	assertType('array{a: 1|2, b: 3|4}', $result);
}

function testIntegerKeysUnion(bool $flag): void {
	$result = [
		'start' => 0,
		...($flag ? [1, 2] : [3]),
	];
	assertType('array{start: 0, 0: 1|3, 1?: 2}', $result);
}

function testAllBranchesSameKeys(bool $flag): void {
	$result = [
		...($flag ? ['a' => 1, 'b' => 2] : ['a' => 3, 'b' => 4]),
	];
	assertType('array{a: 1|3, b: 2|4}', $result);
}

/** @param 'x'|'y'|'z' $variant */
function testThreeBranchUnion(string $variant): void {
	if ($variant === 'x') {
		$extra = ['x' => 1];
	} elseif ($variant === 'y') {
		$extra = ['y' => 2];
	} else {
		$extra = [];
	}
	$result = ['base' => true, ...$extra];
	assertType('array{base: true, y?: 2, x?: 1}', $result);
}

function testIntegerOnlyUnion(bool $flag): void {
	$result = [
		...($flag ? [1, 2, 3] : [4, 5]),
	];
	assertType('array{0: 1|4, 1: 2|5, 2?: 3}', $result);
}

function testEmptyVsNonEmpty(bool $flag): void {
	$result = [
		...($flag ? ['key' => 'value'] : []),
	];
	assertType("array{key?: 'value'}", $result);
}
