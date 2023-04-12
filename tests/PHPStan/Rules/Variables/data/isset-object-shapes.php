<?php

namespace IssetObjectShapes;

class Foo
{

	/**
	 * @param object{foo: int, bar?: string} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{
		if (isset($o->foo)) {

		}

		if (isset($o->bar)) {

		}

		if (isset($o->baz)) {

		}
	}

}
