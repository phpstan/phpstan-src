<?php // lint <= 7.4

namespace GetenvPHP74;

use function PHPStan\Testing\assertType;

class Foo
{

	public function test(string|null $stringOrNull, mixed $mixed)
	{
		assertType('string|false', getenv(null));
		assertType('array<string, string>', getenv());
		assertType('string|false', getenv('foo'));

		assertType('string|false', getenv($stringOrNull));
		assertType('string|false', getenv($mixed));
	}

}
