<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14708;

use function PHPStan\Testing\assertSuperType;
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
	assertSuperType('array{a: 1|2, b: 3|4}', $result);
	assertType('array{a: 1, b: 4}|array{a: 2, b: 3}', $result);
}

function testIntegerKeysUnion(bool $flag): void {
	$result = [
		'start' => 0,
		...($flag ? [1, 2] : [3]),
	];
	assertSuperType('array{start: 0, 0: 1|3, 1?: 2}', $result);
	assertType('array{start: 0, 0: 1, 1: 2}|array{start: 0, 0: 3}', $result);
}

function testAllBranchesSameKeys(bool $flag): void {
	$result = [
		...($flag ? ['a' => 1, 'b' => 2] : ['a' => 3, 'b' => 4]),
	];
	assertSuperType('array{a: 1|3, b: 2|4}', $result);
	assertType('array{a: 1, b: 2}|array{a: 3, b: 4}', $result);
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
	assertSuperType('array{base: true, y?: 2, x?: 1}', $result);
	assertType('array{base: true, x: 1}|array{base: true, y?: 2}', $result);
}

function testIntegerOnlyUnion(bool $flag): void {
	$result = [
		...($flag ? [1, 2, 3] : [4, 5]),
	];
	assertSuperType('array{0: 1|4, 1: 2|5, 2?: 3}', $result);
	assertType('array{1, 2, 3}|array{4, 5}', $result);
}

function testEmptyVsNonEmpty(bool $flag): void {
	$result = [
		...($flag ? ['key' => 'value'] : []),
	];
	assertSuperType("array{key?: 'value'}", $result);
	assertType("array{}|array{key: 'value'}", $result);
}

function testStringKeyBranchAndIntegerKeyBranch(bool $flag): void {
	// integer keys are renumbered, string keys are kept
	$result = [
		9,
		...($flag ? ['a' => 1] : [5]),
	];
	assertSuperType('array{0: 9, a?: 1, 1?: 5}', $result);
	assertType('array{0: 9, a: 1}|array{9, 5}', $result);
}

function testMixedKeysInBothBranches(bool $flag): void {
	$result = [
		...($flag ? ['a' => 1, 7] : [5, 'a' => 2]),
	];
	assertSuperType('array{a: 1|2, 0: 5|7}', $result);
	assertType('array{0: 5, a: 2}|array{a: 1, 0: 7}', $result);
}
