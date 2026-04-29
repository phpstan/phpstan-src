<?php declare(strict_types = 1);

namespace Bug14552;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<'a'|'b'> $keys
 */
function nonEmptyListForeach(array $keys): void
{
	$out = [];
	foreach ($keys as $k) {
		$out[$k] = 1;
	}
	assertType("non-empty-array{a?: 1, b?: 1}", $out);
}

/**
 * @param list<'a'|'b'> $keys
 */
function possiblyEmptyListForeach(array $keys): void
{
	$out = [];
	foreach ($keys as $k) {
		$out[$k] = 1;
	}
	assertType("array{}|array{a?: 1, b?: 1}", $out);
}

/**
 * @param non-empty-list<'x'|'y'|'z'> $keys
 */
function nonEmptyListThreeKeys(array $keys): void
{
	$out = [];
	foreach ($keys as $k) {
		$out[$k] = true;
	}
	assertType("non-empty-array{x?: true, y?: true, z?: true}", $out);
}

/**
 * Direct assignment (non-foreach) with union key on empty array.
 * @param 'a'|'b' $key
 */
function directAssignment(string $key): void
{
	$arr = [];
	$arr[$key] = 1;
	assertType("non-empty-array{a?: 1, b?: 1}", $arr);
}

/**
 * Direct assignment with integer union key on empty array.
 * @param 0|1|2 $key
 */
function directAssignmentIntKey(int $key): void
{
	$arr = [];
	$arr[$key] = 'val';
	assertType("non-empty-array{0?: 'val', 1?: 'val', 2?: 'val'}", $arr);
}

/**
 * Setting union key on already non-empty array should stay non-empty.
 * @param 'x'|'y' $key
 */
function setOnNonEmptyArray(string $key): void
{
	$arr = ['existing' => 0];
	$arr[$key] = 1;
	assertType("array{existing: 0, x?: 1, y?: 1}", $arr);
}
