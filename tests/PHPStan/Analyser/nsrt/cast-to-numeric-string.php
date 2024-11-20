<?php

namespace CastToNumericString;

use function PHPStan\Testing\assertType;

/**
 * @param int|float|numeric-string $numeric
 * @param numeric $numeric2
 * @param number $number
 * @param positive-int $positive
 * @param negative-int $negative
 * @param 1 $constantInt
 */
function foo(int $a, float $b, $numeric, $numeric2, $number, $positive, $negative, $constantInt): void {
	assertType('lowercase-string&numeric-string&uppercase-string', (string)$a);
	assertType('numeric-string&uppercase-string', (string)$b);
	assertType('numeric-string', (string)$numeric);
	assertType('numeric-string', (string)$numeric2);
	assertType('numeric-string&uppercase-string', (string)$number);
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', (string)$positive);
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', (string)$negative);
	assertType("'1'", (string)$constantInt);
}

/**
 * @param int|float|numeric-string $numeric
 * @param numeric $numeric2
 * @param number $number
 * @param positive-int $positive
 * @param negative-int $negative
 * @param 1 $constantInt
 */
function concatEmptyString(int $a, float $b, $numeric, $numeric2, $number, $positive, $negative, $constantInt): void {
	assertType('lowercase-string&numeric-string&uppercase-string', '' . $a);
	assertType('numeric-string&uppercase-string', '' . $b);
	assertType('numeric-string', '' . $numeric);
	assertType('numeric-string', '' . $numeric2);
	assertType('numeric-string&uppercase-string', '' . $number);
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', '' . $positive);
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', '' . $negative);
	assertType("'1'", '' . $constantInt);

	assertType('lowercase-string&numeric-string&uppercase-string', $a . '');
	assertType('numeric-string&uppercase-string', $b . '');
	assertType('numeric-string', $numeric . '');
	assertType('numeric-string', $numeric2 . '');
	assertType('numeric-string&uppercase-string', $number . '');
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $positive . '');
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $negative . '');
	assertType("'1'", $constantInt . '');
}

function concatAssignEmptyString(int $i, float $f) {
	$i .= '';
	assertType('lowercase-string&numeric-string&uppercase-string', $i);

	$s = '';
	$s .= $f;
	assertType('numeric-string&uppercase-string', $s);
}

/**
 * @param int<0, max> $positive
 * @param int<min, 0> $negative
 */
function integerRangeToString($positive, $negative)
{
	assertType('lowercase-string&numeric-string&uppercase-string', (string) $positive);
	assertType('lowercase-string&numeric-string&uppercase-string', (string) $negative);

	if ($positive !== 0) {
		assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', (string) $positive);
	}
	if ($negative !== 0) {
		assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', (string) $negative);
	}
}
