<?php // lint < 8.0

namespace GetenvPHP74;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param string|null $stringOrNull
	 * @param mixed       $mixed
	 */
	public function test($stringOrNull, $mixed)
	{
		assertType('string|false', getenv(null));
		assertType('array<string, string>', getenv());
		assertType('string|false', getenv('foo'));

		assertType('string|false', getenv($stringOrNull));
		assertType('string|false', getenv($mixed));
	}

}
