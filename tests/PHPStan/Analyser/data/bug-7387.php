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

		assertType('numeric-string', sprintf('%14F', $i));
		assertType('numeric-string', sprintf('%14F', $f));
		assertType('numeric-string', sprintf('%14F', $s));
	}

	public function specifiers(int $i) {
		// https://3v4l.org/fmVIg
		assertType('non-empty-string', sprintf('%14s', $i));

		assertType('numeric-string', sprintf('%14b', $i));
		assertType('numeric-string', sprintf('%14d', $i));
		assertType('numeric-string', sprintf('%14e', $i));
		assertType('numeric-string', sprintf('%14E', $i));
		assertType('numeric-string', sprintf('%14f', $i));
		assertType('numeric-string', sprintf('%14F', $i));
		assertType('numeric-string', sprintf('%14o', $i));
		assertType('numeric-string', sprintf('%14u', $i));
		assertType('numeric-string', sprintf('%14x', $i));
		assertType('numeric-string', sprintf('%14X', $i));

	}
}
