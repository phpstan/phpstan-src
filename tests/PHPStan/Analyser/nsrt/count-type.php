<?php

namespace CountType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array $nonEmpty
	 */
	public function doFoo(
		array $nonEmpty
	)
	{
		assertType('int<1, max>', count($nonEmpty));
		assertType('int<1, max>', sizeof($nonEmpty));
	}

}
