<?php

namespace PropertyArray;

use function PHPStan\Testing\assertType;

class Foo
{

	private $property;

	public function doFoo()
	{
		assertType('mixed', $this->property);
		$this->property = [];
		assertType('array{}', $this->property);
		assertType('*ERROR*', $this->property['foo']);
		$this->property['foo'] = 1;
		assertType('array{foo: 1}', $this->property);
		assertType('1', $this->property['foo']);
	}

}
