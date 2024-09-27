<?php

namespace Bug7387;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param int<-1, 5> $intRange
	 */
	public function inputTypes(int $i, float $f, string $s, int $intRange) {
		// https://3v4l.org/iXaDX
		assertType('numeric-string', sprintf('%.14F', $i));
		assertType('numeric-string', sprintf('%.14F', $f));
		assertType('numeric-string', sprintf('%.14F', $s));

		assertType('numeric-string', sprintf('%1.14F', $i));
		assertType('numeric-string', sprintf('%2.14F', $f));
		assertType('numeric-string', sprintf('%3.14F', $s));

		assertType('numeric-string', sprintf('%14F', $i));
		assertType('numeric-string', sprintf('%14F', $f));
		assertType('numeric-string', sprintf('%14F', $s));

		assertType("'-1'|'0'|'1'|'2'|'3'|'4'|'5'", sprintf('%s', $intRange));
		assertType("' 0'|' 1'|' 2'|' 3'|' 4'|' 5'|'-1'", sprintf('%2s', $intRange));
	}

	public function specifiers(int $i) {
		// https://3v4l.org/fmVIg
		assertType('numeric-string', sprintf('%14s', $i));

		assertType('numeric-string', sprintf('%d', $i));

		assertType('numeric-string', sprintf('%14b', $i));
		assertType('non-falsy-string', sprintf('%14c', $i)); // binary string
		assertType('numeric-string', sprintf('%14d', $i));
		assertType('numeric-string', sprintf('%14e', $i));
		assertType('numeric-string', sprintf('%14E', $i));
		assertType('numeric-string', sprintf('%14f', $i));
		assertType('numeric-string', sprintf('%14F', $i));
		assertType('numeric-string', sprintf('%14g', $i));
		assertType('numeric-string', sprintf('%14G', $i));
		assertType('numeric-string', sprintf('%14h', $i));
		assertType('numeric-string', sprintf('%14H', $i));
		assertType('numeric-string', sprintf('%14o', $i));
		assertType('numeric-string', sprintf('%14u', $i));
		assertType('numeric-string', sprintf('%14x', $i));
		assertType('numeric-string', sprintf('%14X', $i));

	}

	/**
	 * @param positive-int $posInt
	 * @param negative-int $negInt
	 * @param int<1, 5>    $nonZeroIntRange
	 * @param int<-1, 5>   $intRange
	 */
	public function positionalArgs($mixed, int $i, float $f, string $s, int $posInt, int $negInt, int $nonZeroIntRange, int $intRange) {
		// https://3v4l.org/vVL0c
		assertType('numeric-string', sprintf('%2$6s', $mixed, $i));
		assertType('non-falsy-string&numeric-string', sprintf('%2$6s', $mixed, $posInt));
		assertType('non-falsy-string&numeric-string', sprintf('%2$6s', $mixed, $negInt));
		assertType("'     1'|'     2'|'     3'|'     4'|'     5'", sprintf('%2$6s', $mixed, $nonZeroIntRange));

		// https://3v4l.org/1ECIq
		assertType('non-falsy-string', sprintf("%2$'#6s", $mixed, 1));
		assertType('non-falsy-string', sprintf("%2$'#6s", $mixed, $i));
		assertType('non-falsy-string', sprintf("%2$'#6s", $mixed, $posInt));
		assertType('non-falsy-string', sprintf("%2$'#6s", $mixed, $negInt));
		assertType("'     0'|'     1'|'     2'|'     3'|'     4'|'     5'|'    -1'", sprintf('%2$6s', $mixed, $intRange));
		assertType('non-falsy-string', sprintf("%2$'#6s", $mixed, $nonZeroIntRange));

		assertType("'     1'", sprintf('%2$6s', $mixed, 1));
		assertType("'     1'", sprintf('%2$6s', $mixed, '1'));
		assertType("'   abc'", sprintf('%2$6s', $mixed, 'abc'));
		assertType("'     0'|'     1'|'     2'|'     3'|'     4'|'     5'|'    -1'", sprintf('%2$6s', $mixed, $intRange));
		assertType("'1'", sprintf('%2$s', $mixed, 1));
		assertType("'1'", sprintf('%2$s', $mixed, '1'));
		assertType("'abc'", sprintf('%2$s', $mixed, 'abc'));
		assertType("'-1'|'0'|'1'|'2'|'3'|'4'|'5'", sprintf('%2$s', $mixed, $intRange));

		assertType('numeric-string', sprintf('%2$.14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$.14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$.14F', $mixed, $s));

		assertType('numeric-string', sprintf('%2$1.14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$2.14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$3.14F', $mixed, $s));

		assertType('numeric-string', sprintf('%2$14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$14F', $mixed, $s));

		assertType('string', sprintf('%10$14F', $mixed, $s));
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
		assertType('lowercase-string&numeric-string', vsprintf("%4d", explode('-', '1988-8-1')));
		assertType('numeric-string', vsprintf("%4d", $array));
		assertType('lowercase-string&numeric-string', vsprintf("%4d", ['123']));
		assertType('\'123\'', vsprintf("%s", ['123']));
		// too many arguments.. php silently allows it
		assertType('lowercase-string&numeric-string', vsprintf("%4d", ['123', '456']));
	}
}
