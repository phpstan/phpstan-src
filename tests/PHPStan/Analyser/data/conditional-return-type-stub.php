<?php

namespace ConditionalReturnTypeStub;

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

function (Foo $f): void {
	assertType('int', $f->doFoo(1));
	assertType('string', $f->doFoo("foo"));
};


function (Bar $b): void {
	assertType('int', $b->doFoo(1));
	assertType('string', $b->doFoo("foo"));
};
