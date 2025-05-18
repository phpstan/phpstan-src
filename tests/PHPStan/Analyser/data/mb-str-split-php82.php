<?php

namespace MbStrSplitPHP82;

use function PHPStan\Testing\assertType;

class MbStrSplit {
	public function legacyTest(): void
	{
		/** @var string $string */
		$string = doFoo();

		$mbStrSplitConstantStringWithoutDefinedParameters = mb_str_split();
		assertType('list<string>', $mbStrSplitConstantStringWithoutDefinedParameters);

		$mbStrSplitConstantStringWithoutDefinedSplitLength = mb_str_split('abcdef');
		assertType('array{\'a\', \'b\', \'c\', \'d\', \'e\', \'f\'}', $mbStrSplitConstantStringWithoutDefinedSplitLength);

		$mbStrSplitStringWithoutDefinedSplitLength = mb_str_split($string);
		assertType('list<non-empty-string>', $mbStrSplitStringWithoutDefinedSplitLength);

		$mbStrSplitConstantStringWithOneSplitLength = mb_str_split('abcdef', 1);
		assertType('array{\'a\', \'b\', \'c\', \'d\', \'e\', \'f\'}', $mbStrSplitConstantStringWithOneSplitLength);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLength = mb_str_split('abcdef', 999);
		assertType('array{\'abcdef\'}', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLength);

		$mbStrSplitConstantStringWithFailureSplitLength = mb_str_split('abcdef', 0);
		assertType('*NEVER*', $mbStrSplitConstantStringWithFailureSplitLength);

		$mbStrSplitConstantStringWithInvalidSplitLengthType = mb_str_split('abcdef', []);
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithInvalidSplitLengthType);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1);
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}|array{'g', 'h', 'i', 'j', 'k', 'l'}", $mbStrSplitConstantStringWithVariableStringAndConstantSplitLength);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2);
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLength);

		$mbStrSplitConstantStringWithOneSplitLengthAndValidEncoding = mb_str_split('abcdef', 1, 'UTF-8');
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}", $mbStrSplitConstantStringWithOneSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithOneSplitLengthAndInvalidEncoding = mb_str_split('abcdef', 1, 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithOneSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithOneSplitLengthAndVariableEncoding = mb_str_split('abcdef', 1, doFoo());
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithOneSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndValidEncoding = mb_str_split('abcdef', 999, 'UTF-8');
		assertType("array{'abcdef'}", $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndValidEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndInvalidEncoding = mb_str_split('abcdef', 999, 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndVariableEncoding = mb_str_split('abcdef', 999, doFoo());
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndValidEncoding = mb_str_split('abcdef', 0, 'UTF-8');
		assertType('*NEVER*', $mbStrSplitConstantStringWithFailureSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndInvalidEncoding = mb_str_split('abcdef', 0, 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithFailureSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndVariableEncoding = mb_str_split('abcdef', 0, doFoo());
		assertType('*NEVER*', $mbStrSplitConstantStringWithFailureSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndValidEncoding = mb_str_split('abcdef', [], 'UTF-8');
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndValidEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndInvalidEncoding = mb_str_split('abcdef', [], 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndInvalidEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndVariableEncoding = mb_str_split('abcdef', [], doFoo());
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndVariableEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndValidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, 'UTF-8');
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}|array{'g', 'h', 'i', 'j', 'k', 'l'}", $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndInvalidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndVariableEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, doFoo());
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndValidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, 'UTF-8');
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndInvalidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, 'FAKE');
		assertType('*NEVER*', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndVariableEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, doFoo());
		assertType('non-empty-list<non-empty-string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndVariableEncoding);
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
		assertType('list<non-empty-string>', mb_str_split($string));
		assertType('non-empty-list<non-empty-string>', mb_str_split($nonEmptyString));
		assertType('non-empty-list<non-empty-string>', mb_str_split($nonFalsyString));

		assertType('list<non-empty-string>', mb_str_split($string, $integer));
		assertType('non-empty-list<non-empty-string>', mb_str_split($nonEmptyString, $integer));
		assertType('non-empty-list<non-empty-string>', mb_str_split($nonFalsyString, $integer));
	}
}
