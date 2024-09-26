<?php

namespace ExplodePhp80;

use function PHPStan\Testing\assertType;

class ExplodingStrings
{
	public function doFoo(string $s): void
	{
		assertType('non-empty-list<lowercase-string>', explode($s, 'foo'));
		assertType('non-empty-list<string>', explode($s, 'FOO'));
	}
}
