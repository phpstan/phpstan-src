<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9461;

use function array_key_exists;
use function PHPStan\Testing\assertType;

function test(): void {
	/** @var array<0|string, mixed> */
	$defaults = ['test', 'a' => 1];

	assertType('array<0|string, mixed>', $defaults);
	if (!array_key_exists(0, $defaults)) {
		assertType('array<string, mixed>', $defaults);
	} else {
		assertType('non-empty-array<0|string, mixed>&hasOffset(0)', $defaults);
	}
}

function testStringKey(): void {
	/** @var array<'foo'|int, mixed> */
	$arr = [];

	if (!array_key_exists('foo', $arr)) {
		assertType('array<int, mixed>', $arr);
	} else {
		assertType("non-empty-array<'foo'|int, mixed>&hasOffset('foo')", $arr);
	}
}

function testGenericIntKey(): void {
	/** @var array<int, mixed> */
	$arr = [];

	if (!array_key_exists(0, $arr)) {
		assertType('array<int<min, -1>|int<1, max>, mixed>', $arr);
	}
}

function testKeyExists(): void {
	/** @var array<0|string, mixed> */
	$arr = [];

	if (!key_exists(0, $arr)) {
		assertType('array<string, mixed>', $arr);
	}
}

function testNonEmptyArray(): void {
	/** @var non-empty-array<0|string, mixed> */
	$arr = ['test'];

	assertType('non-empty-array<0|string, mixed>', $arr);
	if (!array_key_exists(0, $arr)) {
		assertType('non-empty-array<string, mixed>', $arr);
	} else {
		assertType('non-empty-array<0|string, mixed>&hasOffset(0)', $arr);
	}
}

function testIssetDoesNotNarrowKeyType(): void {
	/** @var array<0|string, mixed> */
	$arr = [];

	if (!isset($arr[0])) {
		// isset also checks for null, so !isset doesn't mean key doesn't exist
		assertType('array<0|string, mixed>', $arr);
	} else {
		assertType('non-empty-array<0|string, mixed>&hasOffsetValue(0, mixed~null)', $arr);
	}
}

/**
 * @param array<0|string, mixed>|string $label
 * @return array<string, mixed>
 */
function makeSeedFromLabel($label = []): array
{
	$defaults = is_array($label) ? $label : [$label];
	assertType('array<0|string, mixed>', $defaults);

	if (array_key_exists(0, $defaults)) {
		$defaults['content'] = $defaults[0];
		unset($defaults[0]);
	}
	assertType('array<string, mixed>', $defaults);

	return $defaults;
}
