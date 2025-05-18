<?php

namespace MbStrSplitPHP80;

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
		assertType('list<string>', $mbStrSplitStringWithoutDefinedSplitLength);

		$mbStrSplitConstantStringWithOneSplitLength = mb_str_split('abcdef', 1);
		assertType('array{\'a\', \'b\', \'c\', \'d\', \'e\', \'f\'}', $mbStrSplitConstantStringWithOneSplitLength);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLength = mb_str_split('abcdef', 999);
		assertType('array{\'abcdef\'}', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLength);

		$mbStrSplitConstantStringWithFailureSplitLength = mb_str_split('abcdef', 0);
		assertType('false', $mbStrSplitConstantStringWithFailureSplitLength);

		$mbStrSplitConstantStringWithInvalidSplitLengthType = mb_str_split('abcdef', []);
		assertType('list<string>', $mbStrSplitConstantStringWithInvalidSplitLengthType);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1);
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}|array{'g', 'h', 'i', 'j', 'k', 'l'}", $mbStrSplitConstantStringWithVariableStringAndConstantSplitLength);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2);
		assertType('list<string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLength);

		$mbStrSplitConstantStringWithOneSplitLengthAndValidEncoding = mb_str_split('abcdef', 1, 'UTF-8');
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}", $mbStrSplitConstantStringWithOneSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithOneSplitLengthAndInvalidEncoding = mb_str_split('abcdef', 1, 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithOneSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithOneSplitLengthAndVariableEncoding = mb_str_split('abcdef', 1, doFoo());
		assertType('list<string>', $mbStrSplitConstantStringWithOneSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndValidEncoding = mb_str_split('abcdef', 999, 'UTF-8');
		assertType("array{'abcdef'}", $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndValidEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndInvalidEncoding = mb_str_split('abcdef', 999, 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndVariableEncoding = mb_str_split('abcdef', 999, doFoo());
		assertType('list<string>', $mbStrSplitConstantStringWithGreaterSplitLengthThanStringLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndValidEncoding = mb_str_split('abcdef', 0, 'UTF-8');
		assertType('false', $mbStrSplitConstantStringWithFailureSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndInvalidEncoding = mb_str_split('abcdef', 0, 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithFailureSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithFailureSplitLengthAndVariableEncoding = mb_str_split('abcdef', 0, doFoo());
		assertType('false', $mbStrSplitConstantStringWithFailureSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndValidEncoding = mb_str_split('abcdef', [], 'UTF-8');
		assertType('list<string>', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndValidEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndInvalidEncoding = mb_str_split('abcdef', [], 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndInvalidEncoding);

		$mbStrSplitConstantStringWithInvalidSplitLengthTypeAndVariableEncoding = mb_str_split('abcdef', [], doFoo());
		assertType('list<string>', $mbStrSplitConstantStringWithInvalidSplitLengthTypeAndVariableEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndValidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, 'UTF-8');
		assertType("array{'a', 'b', 'c', 'd', 'e', 'f'}|array{'g', 'h', 'i', 'j', 'k', 'l'}", $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndInvalidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndVariableEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1, doFoo());
		assertType('list<string>', $mbStrSplitConstantStringWithVariableStringAndConstantSplitLengthAndVariableEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndValidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, 'UTF-8');
		assertType('list<string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndValidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndInvalidEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, 'FAKE');
		assertType('false', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndInvalidEncoding);

		$mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndVariableEncoding = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2, doFoo());
		assertType('list<string>', $mbStrSplitConstantStringWithVariableStringAndVariableSplitLengthAndVariableEncoding);
	}
}
