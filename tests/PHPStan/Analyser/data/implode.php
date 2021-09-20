<?php

namespace ImplodeErrors;

use function PHPStan\Testing\assertType;

class Foo
{
	public function invalidArgs(): void
	{
		assertType('*ERROR*', implode('',['12','123',['1234','12345']]));
                assertType('*ERROR*', implode('',[['1234','12345']]));
	}
}
