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
	assertType('decimal-int-string', (string)$a);
	assertType('numeric-string&uppercase-string', (string)$b);
	assertType('numeric-string', (string)$numeric);
	assertType('numeric-string', (string)$numeric2);
	assertType('numeric-string&uppercase-string', (string)$number);
	assertType('decimal-int-string&non-falsy-string', (string)$positive);
	assertType('decimal-int-string&non-falsy-string', (string)$negative);
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
	assertType('decimal-int-string', '' . $a);
	assertType('numeric-string&uppercase-string', '' . $b);
	assertType('numeric-string', '' . $numeric);
	assertType('numeric-string', '' . $numeric2);
	assertType('numeric-string&uppercase-string', '' . $number);
	assertType('decimal-int-string&non-falsy-string', '' . $positive);
	assertType('decimal-int-string&non-falsy-string', '' . $negative);
	assertType("'1'", '' . $constantInt);

	assertType('decimal-int-string', $a . '');
	assertType('numeric-string&uppercase-string', $b . '');
	assertType('numeric-string', $numeric . '');
	assertType('numeric-string', $numeric2 . '');
	assertType('numeric-string&uppercase-string', $number . '');
	assertType('decimal-int-string&non-falsy-string', $positive . '');
	assertType('decimal-int-string&non-falsy-string', $negative . '');
	assertType("'1'", $constantInt . '');
}

function concatAssignEmptyString(int $i, float $f) {
	$i .= '';
	assertType('decimal-int-string', $i);

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
	assertType('decimal-int-string', (string) $positive);
	assertType('decimal-int-string', (string) $negative);

	if ($positive !== 0) {
		assertType('decimal-int-string&non-falsy-string', (string) $positive);
	}
	if ($negative !== 0) {
		assertType('decimal-int-string&non-falsy-string', (string) $negative);
	}
}
