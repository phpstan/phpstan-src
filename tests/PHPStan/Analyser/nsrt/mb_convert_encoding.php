<?php

namespace PHPStan\Analyser\nsrt\mb_convert_encoding;

/**
 * @param array{foo: string, bar: int} $structuredArray
 * @param list<string> $stringList
 * @param list<int> $intList
 */
function test_mb_convert_encoding(
	mixed $mixed,
	string $string,
	array $mixedArray,
	array $structuredArray,
	array $stringList,
	array $intList,
): void {
	\PHPStan\Testing\assertType('(array<array<bool|float|int|string|null>|bool|float|int|string|null>|string|false)', mb_convert_encoding($mixed, 'UTF-8'));
	\PHPStan\Testing\assertType('(string|false)', mb_convert_encoding($string, 'UTF-8'));
	\PHPStan\Testing\assertType('(array|false)', mb_convert_encoding($mixedArray, 'UTF-8'));
	\PHPStan\Testing\assertType('(array{foo: string, bar: int}|false)', mb_convert_encoding($structuredArray, 'UTF-8'));
	\PHPStan\Testing\assertType('(list<string>|false)', mb_convert_encoding($stringList, 'UTF-8'));
	\PHPStan\Testing\assertType('(list<int>|false)', mb_convert_encoding($intList, 'UTF-8'));
};
