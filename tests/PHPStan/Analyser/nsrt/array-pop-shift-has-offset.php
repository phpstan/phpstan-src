<?php declare(strict_types = 1);

namespace ArrayPopShiftHasOffset;

use function PHPStan\Testing\assertType;

function testPop(string $addressLine): void
{
	$words = explode(' ', $addressLine);

	if (count($words) < 3) {
		return;
	}

	assertType('non-empty-list<string>&hasOffsetValue(1, string)&hasOffsetValue(2, string)', $words);
	$a = array_pop($words);
	assertType('string', $a);
	assertType('non-empty-list<string>&hasOffset(0)&hasOffset(1)', $words);
	$b = array_pop($words);
	assertType('string', $b);
	assertType('non-empty-list<string>&hasOffset(0)', $words);
	$c = array_pop($words);
	assertType('string', $c);
	assertType('list<string>', $words);
	$d = array_pop($words);
	assertType('string|null', $d);

	// the pattern this locks in: both pops inside the literal stay strings
	$words2 = explode(' ', $addressLine);
	if (count($words2) < 3) {
		return;
	}
	$lastTwo = [array_pop($words2), array_pop($words2)];
	assertType('array{string, string}', $lastTwo);
}

function testShift(string $addressLine): void
{
	$words = explode(' ', $addressLine);

	if (count($words) < 3) {
		return;
	}

	$a = array_shift($words);
	assertType('string', $a);
	assertType('non-empty-list<string>&hasOffsetValue(0, string)&hasOffsetValue(1, string)', $words);
	$b = array_shift($words);
	assertType('string', $b);
	assertType('non-empty-list<string>&hasOffsetValue(0, string)', $words);
	$c = array_shift($words);
	assertType('string', $c);
	assertType('list<string>', $words);
	$d = array_shift($words);
	assertType('string|null', $d);
}

/**
 * A list can never carry a string offset - narrowing by one is provably false,
 * so the list branches only ever see integer offsets.
 *
 * @param list<string> $list
 */
function testStringOffsetOnListIsImpossible(array $list): void
{
	assertType('false', isset($list['foo']));
}

/**
 * With a string offset in the mix the type is not a list, so pop/shift keep
 * their previous behavior: pop may remove any known offset (iteration order
 * decides), so all offset knowledge is dropped.
 *
 * @param array<string> $arr
 */
function testPopMixedOffsets(array $arr): void
{
	if (!array_key_exists('name', $arr)) {
		return;
	}
	if (!array_key_exists(0, $arr)) {
		return;
	}
	if (!array_key_exists(1, $arr)) {
		return;
	}

	assertType("non-empty-array<string>&hasOffset('name')&hasOffset(0)&hasOffset(1)", $arr);
	$a = array_pop($arr);
	assertType('string', $a);
	assertType('array<string>', $arr);
}

/**
 * @param array<string> $arr
 */
function testShiftMixedOffsets(array $arr): void
{
	if (!array_key_exists('name', $arr)) {
		return;
	}
	if (!array_key_exists(0, $arr)) {
		return;
	}
	if (!array_key_exists(1, $arr)) {
		return;
	}

	assertType("non-empty-array<string>&hasOffset('name')&hasOffset(0)&hasOffset(1)", $arr);
	$a = array_shift($arr);
	assertType('string', $a);
	assertType('array<string>', $arr);
}
