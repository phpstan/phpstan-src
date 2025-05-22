<?php

namespace GetenvPHP80;

use function PHPStan\Testing\assertType;

class Foo
{

	public function test()
	{
		assertType('array<string, string>', getenv(null));
		assertType('array<string, string>', getenv());
		assertType('string|false', getenv('foo'));
	}

}
