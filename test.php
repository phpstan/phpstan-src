<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

class Foo {}
class Bar {}

class HelloWorld
{
	/**
	 * @param array{1?: ?int, 2?: string} $a1
	 * @param array{Foo, Bar} $a2
	 * @param array{1?: int, 2?: string}|int $a3
	 */
	public function constantArrayUnion($a1, $a2, $a3, array $a4): void
	{
		if ($a1 === []) {
			return;
		}
		// assertType('array{0: Foo, 1: Bar|int|null, 2?: string} ', $a1 + $a2);
		assertType('array{}', $a3 + $a4);
	}
}
