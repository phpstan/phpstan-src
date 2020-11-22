<?php

namespace CastToNumericString;

use function PHPStan\Analyser\assertType;

/**
 * @param int|float|numeric-string $numeric
 * @param numeric $numeric2
 * @param number $number
 * @param positive-int $positive
 * @param negative-int $negative
 * @param 1 $constantInt
 */
function foo(int $a, float $b, $numeric, $numeric2, $number, $positive, $negative, $constantInt): void {
	assertType('string&numeric', (string)$a);
	assertType('string&numeric', (string)$b);
	assertType('string&numeric', (string)$numeric);
	assertType('string&numeric', (string)$numeric2);
	assertType('string&numeric', (string)$number);
	assertType('string&numeric', (string)$positive);
	assertType('string&numeric', (string)$negative);
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
	assertType('string&numeric', '' . $a);
	assertType('string&numeric', '' . $b);
	assertType('string&numeric', '' . $numeric);
	assertType('string&numeric', '' . $numeric2);
	assertType('string&numeric', '' . $number);
	assertType('string&numeric', '' . $positive);
	assertType('string&numeric', '' . $negative);
	assertType("'1'", '' . $constantInt);

	assertType('string&numeric', $a . '');
	assertType('string&numeric', $b . '');
	assertType('string&numeric', $numeric . '');
	assertType('string&numeric', $numeric2 . '');
	assertType('string&numeric', $number . '');
	assertType('string&numeric', $positive . '');
	assertType('string&numeric', $negative . '');
	assertType("'1'", $constantInt . '');
}

function concatAssignEmptyString(int $i, float $f) {
	$i .= '';
	assertType('string&numeric', $i);

	$s = '';
	$s .= $f;
	assertType('string&numeric', $s);
}
