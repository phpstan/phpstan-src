<?php

namespace ArrayShapesInPhpDoc;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{0: string, 1: Foo, foo:Bar, Baz} $one
	 * @param array{0: string, 1?: Foo, foo?:Bar} $two
	 * @param array{0?: string, 1?: Foo, foo?:Bar} $three
	 */
	public function doFoo(
		array $one,
		array $two,
		array $three
	)
	{
		assertType('array{0: string, 1: ArrayShapesInPhpDoc\Foo, foo: ArrayShapesInPhpDoc\Bar, 2: ArrayShapesInPhpDoc\Baz}', $one);
		assertType('array{0: string, 1?: ArrayShapesInPhpDoc\Foo, foo?: ArrayShapesInPhpDoc\Bar}', $two);
		assertType('array{0?: string, 1?: ArrayShapesInPhpDoc\Foo, foo?: ArrayShapesInPhpDoc\Bar}', $three);
	}

}
