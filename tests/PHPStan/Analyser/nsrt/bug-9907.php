<?php declare(strict_types = 1);

namespace Bug9907;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param 'foo'|'bar' $key
	 */
	public function sayHello(string $key): void
	{
		$a = [];
		$a['id'] = null;
		$a[$key] = 'string';

		assertType("array{id: null, bar: 'string'}|array{id: null, foo: 'string'}", $a);
	}
}
