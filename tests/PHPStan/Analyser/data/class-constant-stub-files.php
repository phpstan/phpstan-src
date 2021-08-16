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
	assertType('int', Foo::BAR);
	assertType('int', Foo::BAZ);
};
