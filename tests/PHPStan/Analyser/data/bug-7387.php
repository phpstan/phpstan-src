<?php

namespace Bug7387;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function inputTypes(int $i, float $f, string $s) {
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
	}

	public function specifiers(int $i) {
		// https://3v4l.org/fmVIg
		assertType('non-falsy-string', sprintf('%14s', $i));

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

	public function positionalArgs($mixed, int $i, float $f, string $s) {
		// https://3v4l.org/vVL0c
		assertType('non-falsy-string', sprintf('%2$14s', $mixed, $i));

		assertType('numeric-string', sprintf('%2$.14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$.14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$.14F', $mixed, $s));

		assertType('numeric-string', sprintf('%2$1.14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$2.14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$3.14F', $mixed, $s));

		assertType('numeric-string', sprintf('%2$14F', $mixed, $i));
		assertType('numeric-string', sprintf('%2$14F', $mixed, $f));
		assertType('numeric-string', sprintf('%2$14F', $mixed, $s));

		assertType('numeric-string', sprintf('%10$14F', $mixed, $s));
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
		assertType('numeric-string', vsprintf("%4d", explode('-', '1988-8-1')));
		assertType('numeric-string', vsprintf("%4d", $array));
		assertType('numeric-string', vsprintf("%4d", ['123']));
		assertType('non-falsy-string', vsprintf("%s", ['123']));
		// too many arguments.. php silently allows it
		assertType('numeric-string', vsprintf("%4d", ['123', '456']));
	}
}
