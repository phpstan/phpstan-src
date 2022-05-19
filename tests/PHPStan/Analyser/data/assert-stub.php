<?php

namespace AssertStub;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo($x)
	{

	}

}

class Bar extends Foo
{

	public function doFoo($x)
	{

	}

}

function (Foo $f, $x): void {
	if ($f->doFoo($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed', $x);
	}
};


function (Bar $b, $x): void {
	if ($b->doFoo($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed', $x);
	}
};
