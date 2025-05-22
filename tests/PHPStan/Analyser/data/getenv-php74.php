<?php

namespace GetenvPHP74;

use function PHPStan\Testing\assertType;

class Foo
{

	public function test()
	{
		assertType('string|false', getenv(null));
		assertType('array<string, string>', getenv());
		assertType('string|false', getenv('foo'));
	}

}
