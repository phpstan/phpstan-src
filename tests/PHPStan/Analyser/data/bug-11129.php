<?php

namespace Bug11129;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param positive-int $positiveInt
	 * @param negative-int $negativeInt
	 * @param numeric-string $numericString
	 * @param 0|'0'|'1'|'2' $positiveConstStrings
	 * @param 0|-1|'2' $maybeNegativeConstStrings
	 * @param 0|1|'a' $maybeNonNumericConstStrings
	 * @param 0|1|0.2 $maybeFloatConstStrings
	 */
	public function foo(
		int $i, $positiveInt, $negativeInt, $positiveConstStrings,
		$numericString,
		$maybeNegativeConstStrings, $maybeNonNumericConstStrings, $maybeFloatConstStrings,
		bool $bool, float $float
	): void {
		assertType('non-falsy-string', '0'.$i);
		assertType('non-falsy-string&numeric-string', $i.'0');

		assertType('non-falsy-string&numeric-string', '0'.$positiveInt);
		assertType('non-falsy-string&numeric-string', $positiveInt.'0');

		assertType('non-falsy-string', '0'.$negativeInt);
		assertType('non-falsy-string&numeric-string', $negativeInt.'0');

		assertType("'00'|'01'|'02'", '0'.$positiveConstStrings);
		assertType( "'00'|'10'|'20'", $positiveConstStrings.'0');

		assertType("'0-1'|'00'|'02'", '0'.$maybeNegativeConstStrings);
		assertType("'-10'|'00'|'20'", $maybeNegativeConstStrings.'0');

		assertType("'00'|'01'|'0a'", '0'.$maybeNonNumericConstStrings);
		assertType("'00'|'10'|'a0'", $maybeNonNumericConstStrings.'0');

		assertType('non-falsy-string&numeric-string', $i.$positiveConstStrings);
		assertType( 'non-falsy-string', $positiveConstStrings.$i);

		assertType('non-falsy-string', $i.$maybeNegativeConstStrings);
		assertType('non-falsy-string', $maybeNegativeConstStrings.$i);

		assertType('non-falsy-string', $i.$maybeNonNumericConstStrings);
		assertType('non-falsy-string', $maybeNonNumericConstStrings.$i);

		assertType('non-falsy-string', $i.$maybeFloatConstStrings); // could be 'non-falsy-string&numeric-string'
		assertType('non-falsy-string', $maybeFloatConstStrings.$i);

		assertType('non-empty-string&numeric-string', $i.$bool);
		assertType('non-empty-string', $bool.$i);
		assertType('non-empty-string&numeric-string', $positiveInt.$bool); // could be 'non-falsy-string&numeric-string'
		assertType('non-empty-string&numeric-string', $bool.$positiveInt); // could be 'non-falsy-string&numeric-string'
		assertType('non-empty-string&numeric-string', $negativeInt.$bool); // could be 'non-falsy-string&numeric-string'
		assertType('non-empty-string', $bool.$negativeInt);

		assertType('non-falsy-string', $i.$i);
		assertType('non-falsy-string', $negativeInt.$negativeInt);
		assertType('non-falsy-string', $maybeNegativeConstStrings.$negativeInt);
		assertType('non-falsy-string', $negativeInt.$maybeNegativeConstStrings);

		// https://3v4l.org/BCS2K
		assertType('non-falsy-string', $float.$float);
		assertType('non-falsy-string&numeric-string', $float.$positiveInt);
		assertType('non-falsy-string', $float.$negativeInt);
		assertType('non-falsy-string', $float.$i);
		assertType('non-falsy-string', $i.$float); // could be 'non-falsy-string&numeric-string'
		assertType('non-falsy-string', $numericString.$float);
		assertType('non-falsy-string', $numericString.$maybeFloatConstStrings);

		// https://3v4l.org/Ia4r0
		$scientificFloatAsString = '3e4';
		assertType('non-falsy-string', $numericString.$scientificFloatAsString);
		assertType('non-falsy-string', $i.$scientificFloatAsString);
		assertType('non-falsy-string', $scientificFloatAsString.$numericString);
		assertType('non-falsy-string', $scientificFloatAsString.$i);
	}

}
