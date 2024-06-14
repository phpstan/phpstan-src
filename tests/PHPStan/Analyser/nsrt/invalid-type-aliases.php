<?php

namespace InvalidTypeAliases;

use function PHPStan\Testing\assertType;

/** @psalm-type MyObject = what{foo: 'bar'} */
class HelloWorld
{
	/** @psalm-param MyObject $a */
	public function acceptsAlias($a)
	{
		assertType('mixed', $a);
		assertType('mixed', $this->returnsAlias());
	}

	/** @psalm-return MyObject */
	public function returnsAlias()
	{

	}
}
