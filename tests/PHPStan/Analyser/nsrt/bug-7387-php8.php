<?php

namespace Bug7387Php8;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function specifiers(int $i) {
		// https://3v4l.org/fmVIg
		assertType('non-falsy-string&numeric-string', sprintf('%14h', $i));
		assertType('non-falsy-string&numeric-string', sprintf('%14H', $i));
	}

}
