<?php

namespace PropertyExistsObjectShapes;

use function property_exists;

class Foo
{

	/**
	 * @param object{foo: int, bar?: string} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{
		if (property_exists($o, 'foo')) {

		}

		if (property_exists($o, 'bar')) {

		}

		if (property_exists($o, 'baz')) {

		}
	}

}
