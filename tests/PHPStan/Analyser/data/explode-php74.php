<?php

namespace ExplodePhp74;

use function PHPStan\Testing\assertType;

class ExplodingStrings
{
	public function doFoo(string $s): void
	{
		assertType('non-empty-list<lowercase-string>|false', explode($s, 'foo'));
		assertType('non-empty-list<string>|false', explode($s, 'FOO'));
	}
}
