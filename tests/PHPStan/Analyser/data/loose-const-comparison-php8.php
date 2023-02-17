<?php

namespace LooseConstComparisonPhp8;

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
	assertType('false', 0 == "foo");
	assertType('false', 0 == "");
	assertType('true', 42 == " 42");
	assertType('false', 42 == "42foo");

	assertType('false', 0.0 == "");
	assertType('false', 42.0 == "42foo");
	assertType('false', 42 == "42.0foo");
	assertType('false', 42.1 == "42.0foo");
	assertType('false', 42.0 == "42.0foo");


	assertType('false', $i == $nonNumericString);
	assertType('false', $nonNumericString == $i);
	assertType('false', $f == $nonNumericString);
	assertType('false', $nonNumericString == $f);
	if ($i !== 0) {
		assertType('false', $i == $nonNumericString);
		assertType('false', $nonNumericString == $i);
	} else {
		assertType('false', $i == $nonNumericString);
		assertType('false', $nonNumericString == $i);
	}
	if ($f !== 0.0) {
		assertType('false', $f == $nonNumericString);
		assertType('false', $nonNumericString == $f);
	} else {
		assertType('false', $f == $nonNumericString);
		assertType('false', $nonNumericString == $f);
	}
	assertType('false', $nonZeroRange == $nonNumericString);
	assertType('false', $nonNumericString == $nonZeroRange);

	assertType('false', $unionStrings == $i);
	assertType('false', $i == $unionStrings);

	assertType('false', $unionStrings == $f);
	assertType('false', $f == $unionStrings);

	assertType('false', $i == '');
	assertType('false', '' == $i);

	assertType('false', $unionMaybeNumeric == $zero);
	assertType('false', $zero == $unionMaybeNumeric);

	assertType('false', $unionStrings == $zero);
	assertType('false', $zero == $unionStrings);

	assertType('false', $unionMaybeArray == $floatZero);
	assertType('false', $floatZero == $unionMaybeArray);

	assertType('false', $unionNumbers == $unionMaybeArray);
	assertType('false', $unionMaybeArray == $unionNumbers);
}
