<?php

namespace InheritDocFromTrait2;

use function PHPStan\Testing\assertType;

class Foo extends FooParent
{

	/**
	 * {@inheritdoc}
	 */
	public function doFoo($string)
	{
		assertType('string', $string);
	}

}
