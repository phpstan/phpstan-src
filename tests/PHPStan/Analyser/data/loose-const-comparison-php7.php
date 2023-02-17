<?php

namespace LooseConstComparisonPhp7;

use function PHPStan\Testing\assertType;

/**
 * @param int<1, 100> $nonZeroRange
 * @param 'abc' $nonNumericString
 * @param 'a'|'b'|'c' $unionStrings
 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
 * @param 0|1|2|3 $unionNumbers
 * @param 0 $zero
 * @param 0.0 $floatZero
 * @param 'a'|'123'|123|array $unionMaybeArray
 * @return void
 */
function doFoo(
	$nonZeroRange,
	int $i,
	float $f,
	string $nonNumericString,
	$unionStrings,
	$unionMaybeNumeric,
	$unionNumbers,
	$zero,
	$floatZero,
	$unionMaybeArray,
) {
	assertType('true', 0 == "0");
	assertType('true', 0 == "0.0");
	assertType('true', 0 == "foo");
	assertType('true', 0 == "");
	assertType('true', 42 == " 42");
	assertType('true', 42 == "42foo");

	assertType('true', 0.0 == "");
	assertType('true', 42.0 == "42foo");
	assertType('true', 42 == "42.0foo");
	assertType('false', 42.1 == "42.0foo");
	assertType('true', 42.0 == "42.0foo");


	assertType('bool', $i == $nonNumericString);
	assertType('bool', $nonNumericString == $i);
	assertType('bool', $f == $nonNumericString);
	assertType('bool', $nonNumericString == $f);
	if ($i !== 0) {
		assertType('bool', $i == $nonNumericString);
		assertType('bool', $nonNumericString == $i);
	} else {
		assertType('true', $i == $nonNumericString);
		assertType('true', $nonNumericString == $i);
	}
	if ($f !== 0.0) {
		assertType('bool', $f == $nonNumericString);
		assertType('bool', $nonNumericString == $f);
	} else {
		assertType('true', $f == $nonNumericString);
		assertType('true', $nonNumericString == $f);
	}
	assertType('bool', $nonZeroRange == $nonNumericString);
	assertType('bool', $nonNumericString == $nonZeroRange);

	assertType('bool', $unionStrings == $i);
	assertType('bool', $i == $unionStrings);

	assertType('bool', $unionStrings == $f);
	assertType('bool', $f == $unionStrings);

	assertType('bool', $i == '');
	assertType('bool', '' == $i);

	assertType('bool', $unionMaybeNumeric == $zero);
	assertType('bool', $zero == $unionMaybeNumeric);

	assertType('true', $unionStrings == $zero);
	assertType('true', $zero == $unionStrings);

	assertType('bool', $unionMaybeArray == $zero);
	assertType('bool', $zero == $unionMaybeArray);

	assertType('bool', $unionMaybeArray == $floatZero);
	assertType('bool', $floatZero == $unionMaybeArray);

	assertType('bool', $unionNumbers == $unionMaybeArray);
	assertType('bool', $unionMaybeArray == $unionNumbers);
}
