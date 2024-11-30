<?php

namespace ArrayFlip;

use function PHPStan\Testing\assertType;

/**
 * @param int[] $integerList
 */
function foo($integerList)
{
	$flip = array_flip($integerList);
	assertType('array<int, (int|string)>', $flip);
}

/**
 * @param mixed[] $list
 */
function foo3($list)
{
	$flip = array_flip($list);

	assertType('array<(int|string)>', $flip);
}

/**
 * @param array<int, 1|2|3> $array
 */
function foo4($array)
{
	$flip = array_flip($array);
	assertType('array<1|2|3, int>', $flip);
}


/**
 * @param array<1|2|3, string> $array
 */
function foo5($array)
{
	$flip = array_flip($array);
	assertType('array<string, 1|2|3>', $flip);
}

/**
 * @param non-empty-array<1|2|3, 4|5|6> $array
 */
function foo6($array)
{
	$flip = array_flip($array);
	assertType('non-empty-array<4|5|6, 1|2|3>', $flip);
}

/**
 * @param list<1|2|3> $array
 */
function foo7($array)
{
	$flip = array_flip($array);
	assertType('array<1|2|3, int<0, max>>', $flip);
}

function foo8($mixed)
{
	assertType('mixed', $mixed);
	$mixed = array_flip($mixed);
	assertType('array', $mixed);
}

/** @param array<string, int> $array */
function foo10(array $array)
{
	if (array_key_exists('foo', $array)) {
		assertType('array<string, int>&hasOffset(\'foo\')', $array);
		assertType('array<int, string>', array_flip($array));
	}

	if (array_key_exists('foo', $array) && is_int($array['foo'])) {
		assertType("array<string, int>&hasOffsetValue('foo', int)", $array);
		assertType('array<int, string>', array_flip($array));
	}

	if (array_key_exists('foo', $array) && $array['foo'] === 17) {
		assertType("array<string, int>&hasOffsetValue('foo', 17)", $array);
		assertType("array<int, string>&hasOffsetValue(17, 'foo')", array_flip($array));
	}

	if (
		array_key_exists('foo', $array) && $array['foo'] === 17
		&& array_key_exists('bar', $array) && $array['bar'] === 17
	) {
		assertType("array<string, int>&hasOffsetValue('bar', 17)&hasOffsetValue('foo', 17)", $array);
		assertType("*NEVER*", array_flip($array)); // this could be array<string, int>&hasOffsetValue(17, 'bar') according to https://3v4l.org/1TAFk
	}
}
