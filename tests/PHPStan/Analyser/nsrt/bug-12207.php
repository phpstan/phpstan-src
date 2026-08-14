<?php declare(strict_types = 1);

namespace Bug12207;

use Generator;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @return Generator<string, array{0: string, 1: array<string, string>}>
	 */
	public function bar(): Generator
	{
		yield 'foo' => [
			$a = 'string',
			['string' => $a],
		];
	}

	public function baz(): void
	{
		$value = [
			$a = 'string',
			['string' => $a],
		];
		assertType("array{'string', array{string: 'string'}}", $value);
	}

}
