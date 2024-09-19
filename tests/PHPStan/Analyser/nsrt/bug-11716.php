<?php declare(strict_types=1);

namespace Bug11716;

use function PHPStan\Testing\assertType;

class TypeExpression
{
	/**
	 * @return '&'|'|'
	 */
	public function parse(string $glue): string
	{
		$seenGlues = ['|' => false, '&' => false];

		assertType("array{|: false, &: false}", $seenGlues);

		if ($glue !== '') {
			assertType('non-empty-string', $glue);

			\assert(isset($seenGlues[$glue]));
			$seenGlues[$glue] = true;

			assertType("'&'|'|'", $glue);
			assertType('array{|: bool, &: bool}', $seenGlues);
		} else {
			assertType("''", $glue);
		}

		assertType("''|'&'|'|'", $glue);
		assertType("array{|: bool, &: bool}", $seenGlues);

		return array_key_first($seenGlues);
	}
}

/**
 * @param array<int, string> $arr
 * @param array<1|'2', string> $numericKeyArray
 */
function narrowKey($mixed, string $s, int $i, array $generalArr, array $arr, array $numericKeyArray): void {
	if (isset($generalArr[$mixed])) {
		assertType('(int|string)', $mixed); // array-key cast semantics
	} else {
		assertType('mixed', $mixed);
	}
	assertType('mixed', $mixed);

	if (isset($generalArr[$i])) {
		assertType('int', $i);
	} else {
		assertType('int', $i);
	}
	assertType('int', $i);

	if (isset($generalArr[$s])) {
		assertType('string', $s);
	} else {
		assertType('string', $s);
	}
	assertType('string', $s);

	if (isset($arr[$mixed])) {
		assertType('(int|string)', $mixed); // array-key cast semantics
	} else {
		assertType('mixed', $mixed);
	}
	assertType('mixed', $mixed);

	if (isset($arr[$i])) {
		assertType('int', $i);
	} else {
		assertType('int', $i);
	}
	assertType('int', $i);

	if (isset($arr[$s])) {
		assertType('string', $s);
	} else {
		assertType('string', $s);
	}
	assertType('string', $s);

	if (isset($numericKeyArray[$mixed])) {
		assertType("(int|string)", $mixed); // array-key cast semantics
	} else {
		assertType("mixed", $mixed);
	}
	assertType("mixed", $mixed);
}

/**
 * @param array<int, array<string, float>> $arr
 */
function multiDim($mixed, $mixed2, array $arr) {
	if (isset($arr[$mixed])) {
		assertType('(int|string)', $mixed); // array-key cast semantics
	} else {
		assertType('mixed', $mixed);
	}
	assertType('mixed', $mixed);

	if (isset($arr[$mixed]) && isset($arr[$mixed][$mixed2])) {
		// array-key cast semantics
		assertType('(int|string)', $mixed);
		assertType('(int|string)', $mixed2);
	} else {
		assertType('mixed', $mixed);
	}
	assertType('mixed', $mixed);

	if (isset($arr[$mixed][$mixed2])) {
		// array-key cast semantics
		assertType('(int|string)', $mixed);
		assertType('(int|string)', $mixed2);
	} else {
		assertType('mixed', $mixed);
		assertType('mixed', $mixed2);
	}
	assertType('mixed', $mixed);
	assertType('mixed', $mixed2);
}
