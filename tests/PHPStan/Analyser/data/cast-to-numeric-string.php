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
	assertType('numeric-string', (string)$a);
	assertType('numeric-string', (string)$b);
	assertType('numeric-string', (string)$numeric);
	assertType('numeric-string', (string)$numeric2);
	assertType('numeric-string', (string)$number);
	assertType('numeric-string', (string)$positive);
	assertType('numeric-string', (string)$negative);
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
	assertType('numeric-string', '' . $a);
	assertType('numeric-string', '' . $b);
	assertType('numeric-string', '' . $numeric);
	assertType('numeric-string', '' . $numeric2);
	assertType('numeric-string', '' . $number);
	assertType('numeric-string', '' . $positive);
	assertType('numeric-string', '' . $negative);
	assertType("'1'", '' . $constantInt);

	assertType('numeric-string', $a . '');
	assertType('numeric-string', $b . '');
	assertType('numeric-string', $numeric . '');
	assertType('numeric-string', $numeric2 . '');
	assertType('numeric-string', $number . '');
	assertType('numeric-string', $positive . '');
	assertType('numeric-string', $negative . '');
	assertType("'1'", $constantInt . '');
}

function concatAssignEmptyString(int $i, float $f) {
	$i .= '';
	assertType('numeric-string', $i);

	$s = '';
	$s .= $f;
	assertType('numeric-string', $s);
}
