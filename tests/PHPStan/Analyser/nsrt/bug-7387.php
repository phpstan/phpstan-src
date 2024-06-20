<?php

namespace Bug7387;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function inputTypes(int $i, float $f, string $s) {
		// https://3v4l.org/iXaDX
		assertType('non-falsy-string&numeric-string', sprintf('%.14F', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%.14F', $f));
		assertType('non-falsy-string&numeric-string', sprintf('%.14F', $s));

		assertType('non-falsy-string&numeric-string', sprintf('%1.14F', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%2.14F', $f));
		assertType('non-falsy-string&numeric-string', sprintf('%3.14F', $s));

		assertType('non-falsy-string&numeric-string', sprintf('%14F', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14F', $f));
		assertType('non-falsy-string&numeric-string', sprintf('%14F', $s));

		assertType('string', sprintf('%s%s', $s, $s));
		assertType('non-empty-string', sprintf('0%s', $s));
		assertType('non-falsy-string', sprintf('A%s', $s));
		assertType('non-falsy-string', sprintf('ABC%s', $s));
		assertType('non-falsy-string', sprintf('%sABC', $s));
		assertType('non-falsy-string', sprintf('ABC%sABC', $s));
		assertType('non-falsy-string', sprintf('{^/?%s(/\*?)?$}', preg_quote($s)));
	}

	public function specifiers(int $i) {
		// https://3v4l.org/fmVIg
		assertType('non-falsy-string', sprintf('%14s', $i));

		assertType('non-empty-string&numeric-string', sprintf('%d', $i));

		assertType('non-falsy-string&numeric-string', sprintf('%14b', $i));
		assertType('non-falsy-string', sprintf('%14c', $i)); // binary string
		assertType('non-falsy-string&numeric-string', sprintf('%14d', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14e', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14E', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14f', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14F', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14g', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14G', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14h', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14H', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14o', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14u', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14x', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14X', $i));

	}

	public function positionalArgs($mixed, int $i, float $f, string $s) {
		// https://3v4l.org/vVL0c
		assertType('non-falsy-string', sprintf('%2$14s', $mixed, $i));

		assertType('non-falsy-string&numeric-string', sprintf('%2$.14F', $mixed, $i));
		assertType('non-falsy-string&numeric-string', sprintf('%2$.14F', $mixed, $f));
		assertType('non-falsy-string&numeric-string', sprintf('%2$.14F', $mixed, $s));

		assertType('non-falsy-string&numeric-string', sprintf('%2$1.14F', $mixed, $i));
		assertType('non-falsy-string&numeric-string', sprintf('%2$2.14F', $mixed, $f));
		assertType('non-falsy-string&numeric-string', sprintf('%2$3.14F', $mixed, $s));

		assertType('non-falsy-string&numeric-string', sprintf('%2$14F', $mixed, $i));
		assertType('non-falsy-string&numeric-string', sprintf('%2$14F', $mixed, $f));
		assertType('non-falsy-string&numeric-string', sprintf('%2$14F', $mixed, $s));

		// XXX should be string because of invalid arguments count
		assertType('non-falsy-string&numeric-string', sprintf('%10$14F', $mixed, $s));
	}

	public function invalidPositionalArgFormat($mixed, string $s) {
		assertType('string', sprintf('%0$14F', $mixed, $s));
	}

	public function escapedPercent(int $i) {
		// https://3v4l.org/2m50L
		assertType('non-falsy-string', sprintf("%%d", $i));
	}

	public function vsprintf(array $array)
	{
		assertType('non-falsy-string&numeric-string', vsprintf("%4d", explode('-', '1988-8-1')));
		assertType('non-falsy-string&numeric-string', vsprintf("%4d", $array));
		assertType('non-falsy-string&numeric-string', vsprintf("%4d", ['123']));
		assertType('non-empty-string', vsprintf("%s", ['123'])); // could be 'non-falsy-string'
		// too many arguments.. php silently allows it
		assertType('non-falsy-string&numeric-string', vsprintf("%4d", ['123', '456']));
	}

	/**
	 * @param array<string> $arr
	 */
	public function bug11201($arr) {
		assertType('string', sprintf("%s", implode(', ', array_map('intval', $arr))));
		if (count($arr) > 0) {
			assertType('non-falsy-string', sprintf("%s", implode(', ', array_map('intval', $arr))));
		}
	}

	/**
	 * @param positive-int $positiveInt
	 */
	public function testNonStrings(bool $bool, int $int, float $float, $positiveInt) {
		assertType('string', sprintf('%s', $bool));
		if ($bool) {
			assertType("'1'", sprintf('%s', $bool));
		} else {
			assertType("''", sprintf('%s', $bool));
		}

		assertType('non-falsy-string', sprintf('ABC%s', $bool));
		assertType('non-falsy-string', sprintf('%sABC', $bool));
		assertType('non-falsy-string', sprintf('ABC%sABC', $bool));

		assertType('non-empty-string', sprintf('%s', $int));
		assertType('non-falsy-string', sprintf('%s', $positiveInt));
		assertType('non-empty-string', sprintf('%s', $float));
	}
}
