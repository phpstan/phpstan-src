<?php

namespace StrSplitPHP80;

use function PHPStan\Testing\assertType;

class StrSplit {
	public function legacyTest() {
		/** @var string $string */
		$string = doFoo();

		$strSplitConstantStringWithoutDefinedParameters = str_split();
		assertType('non-empty-list<string>', $strSplitConstantStringWithoutDefinedParameters);

		$strSplitConstantStringWithoutDefinedSplitLength = str_split('abcdef');
		assertType('array{\'a\', \'b\', \'c\', \'d\', \'e\', \'f\'}', $strSplitConstantStringWithoutDefinedSplitLength);

		$strSplitStringWithoutDefinedSplitLength = str_split($string);
		assertType('non-empty-list<string>', $strSplitStringWithoutDefinedSplitLength);

		$strSplitConstantStringWithOneSplitLength = str_split('abcdef', 1);
		assertType('array{\'a\', \'b\', \'c\', \'d\', \'e\', \'f\'}', $strSplitConstantStringWithOneSplitLength);

		$strSplitConstantStringWithGreaterSplitLengthThanStringLength = str_split('abcdef', 999);
		assertType('array{\'abcdef\'}', $strSplitConstantStringWithGreaterSplitLengthThanStringLength);

		$strSplitConstantStringWithFailureSplitLength = str_split('abcdef', 0);
		assertType('*NEVER*', $strSplitConstantStringWithFailureSplitLength);

		$strSplitConstantStringWithInvalidSplitLengthType = str_split('abcdef', []);
		assertType('non-empty-list<non-empty-string>', $strSplitConstantStringWithInvalidSplitLengthType);

		$strSplitConstantStringWithVariableStringAndConstantSplitLength = str_split(doFoo() ? 'abcdef' : 'ghijkl', 1);
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}|array{'g', 'h', 'i', 'j', 'k', 'l'}", $strSplitConstantStringWithVariableStringAndConstantSplitLength);

		$strSplitConstantStringWithVariableStringAndVariableSplitLength = str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2);
		assertType('non-empty-list<non-empty-string>', $strSplitConstantStringWithVariableStringAndVariableSplitLength);

	}

	/**
	 * @param non-empty-string $nonEmptyString
	 * @param non-falsy-string $nonFalsyString
	 */
	function doFoo(
		string $string,
		string $nonEmptyString,
		string $nonFalsyString,
		int $integer,
	):void {
		assertType('non-empty-list<string>', str_split($string));
		assertType('non-empty-list<non-empty-string>', str_split($nonEmptyString));
		assertType('non-empty-list<non-empty-string>', str_split($nonFalsyString));

		assertType('non-empty-list<string>', str_split($string, $integer));
		assertType('non-empty-list<non-empty-string>', str_split($nonEmptyString, $integer));
		assertType('non-empty-list<non-empty-string>', str_split($nonFalsyString, $integer));
	}
}
