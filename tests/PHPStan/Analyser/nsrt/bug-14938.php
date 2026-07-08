<?php declare(strict_types = 1);

namespace Bug14938;

use function PHPStan\Testing\assertType;

/** @param array{a?: string} $a */
function withOptionalStringKey(array $a): void
{
	if (array_is_list($a)) {
		assertType('list&list{a?: string}', $a);
	} else {
		assertType('array{a?: string}', $a);
	}
}

/** @param array{0: int, a?: string} $b */
function withOptionalExtraKey(array $b): void
{
	if (array_is_list($b)) {
		assertType('list{int}', $b);
	} else {
		assertType('array{0: int, a?: string}', $b);
	}
}

/** @param array{a: string} $c */
function withMandatoryStringKey(array $c): void
{
	if (array_is_list($c)) {
		assertType('*NEVER*', $c);
	} else {
		assertType('array{a: string}', $c);
	}
}

/** @param array{1: string} $d */
function withMandatoryGapKey(array $d): void
{
	if (array_is_list($d)) {
		assertType('*NEVER*', $d);
	} else {
		assertType('array{1: string}', $d);
	}
}

/** @param array{5?: string} $e */
function withOptionalGapIntKey(array $e): void
{
	if (array_is_list($e)) {
		assertType('array{5?: string}', $e);
	} else {
		assertType('array{5?: string}', $e);
	}
}

/** @param array{-1?: string} $f */
function withOptionalNegativeIntKey(array $f): void
{
	if (array_is_list($f)) {
		assertType('list{-1?: string}&list', $f);
	} else {
		assertType('array{-1?: string}', $f);
	}
}

function builtViaConditionalAssignment(): void
{
	$b = [0 => 'z'];
	if (rand(0, 1)) {
		$b['y'] = 1;
	}
	assertType("array{0: 'z', y?: 1}", $b);
	assertType('bool', array_is_list($b));

	// Two pure lists still merge into a list.
	$c = [0 => 'z'];
	if (rand(0, 1)) {
		$c[1] = 'w';
	}
	assertType("array{0: 'z', 1?: 'w'}", $c);
	assertType('true', array_is_list($c));

	// Merging shapes disjoint save for the empty array still admits the empty list.
	$d = [];
	if (rand(0, 1)) {
		$d['a'] = 1;
	}
	assertType('array{}|array{a: 1}', $d);
	assertType('bool', array_is_list($d));
}
