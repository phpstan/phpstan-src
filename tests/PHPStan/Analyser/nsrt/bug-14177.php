<?php declare(strict_types = 1);

namespace Bug14177Types;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string} $b
	 */
	public function testList(array $b): void
	{
		if (array_key_exists(3, $b)) {
			assertType('list{0: string, 1: string, 2?: string, 3: string}', $b);
		} else {
			assertType('list{0: string, 1: string, 2?: string}', $b);
		}
		assertType('list{0: string, 1: string, 2?: string, 3?: string}', $b);
	}
}
