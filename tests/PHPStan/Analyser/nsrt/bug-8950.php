<?php

namespace Bug8950 {
	interface FooInterface
	{
	}
}

namespace {

	define('BUG_8950_FOO', 'foo');

	use Bug8950\FooInterface;

	/** @var FooInterface $foo */
	$foo = null;

	\PHPStan\Testing\assertType('Bug8950\FooInterface', $foo);
}
