<?php // onlyif PHP_VERSION_ID < 80000

namespace Bug5782bPhp7;

use function PHPStan\Testing\assertType;

class X
{
	public function classMethod(): void
	{
	}

	static public function staticMethod(): void
	{
	}
}

function doFoo(): void {
	assertType('true', is_callable(['Bug5782bPhp7\X', 'staticMethod']));
	assertType('true', is_callable(['Bug5782bPhp7\X', 'classMethod'])); // should be true on php7, false on php8

	assertType('true', is_callable('Bug5782bPhp7\X::staticMethod'));
	assertType('true', is_callable('Bug5782bPhp7\X::classMethod')); // should be true on php7, false on php8

	assertType('true', is_callable([new X(), 'staticMethod']));
	assertType('true', is_callable([new X(), 'classMethod']));
}
