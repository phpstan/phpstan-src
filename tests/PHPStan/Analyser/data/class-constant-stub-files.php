<?php

namespace ClassConstantStubFiles;

use function PHPStan\Testing\assertType;

class Foo
{

	const BAR = 1;

	/** @var string */
	const BAZ = 1;

}

function (): void {
	assertType('1', Foo::BAR);
	assertType('1', Foo::BAZ);

	$foo = new Foo();
	assertType('int', $foo::BAR);
	assertType('int', $foo::BAZ);
};
