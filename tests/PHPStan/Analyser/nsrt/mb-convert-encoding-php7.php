<?php // lint <= 8.0

namespace MbConvertEncodingPHP7;

/**
 * @param 'foo'|'bar' $constantString
 * @param array{foo: string, bar: int, baz: 'foo'} $structuredArray
 * @param list<string> $stringList
 * @param list<int> $intList
 * @param 'foo'|'bar'|array{foo: string, bar: int, baz: 'foo'}|bool $union
 */
function test_mb_convert_encoding(
	mixed $mixed,
	string $constantString,
	string $string,
	array $mixedArray,
	array $structuredArray,
	array $stringList,
	array $intList,
	string|array|bool $union,
	int $int,
): void {
	\PHPStan\Testing\assertType('array|string|false', mb_convert_encoding($mixed, 'UTF-8'));
	\PHPStan\Testing\assertType('string|false', mb_convert_encoding($constantString, 'UTF-8'));
	\PHPStan\Testing\assertType('string|false', mb_convert_encoding($string, 'UTF-8'));
	\PHPStan\Testing\assertType('array|false', mb_convert_encoding($mixedArray, 'UTF-8'));
	\PHPStan\Testing\assertType('array{foo: string, bar: int, baz: string}|false', mb_convert_encoding($structuredArray, 'UTF-8'));
	\PHPStan\Testing\assertType('list<string>|false', mb_convert_encoding($stringList, 'UTF-8'));
	\PHPStan\Testing\assertType('list<int>|false', mb_convert_encoding($intList, 'UTF-8'));
	\PHPStan\Testing\assertType('array{foo: string, bar: int, baz: string}|string|false', mb_convert_encoding($union, 'UTF-8'));
	\PHPStan\Testing\assertType('array|string|false', mb_convert_encoding($int, 'UTF-8'));

	\PHPStan\Testing\assertType('string|false', mb_convert_encoding($string, 'UTF-8', 'auto'));
	\PHPStan\Testing\assertType('string|false', mb_convert_encoding($string, 'UTF-8', ' AUTO '));
};
