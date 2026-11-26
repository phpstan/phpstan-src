<?php declare(strict_types = 1);

namespace AssertIfTrueMergedShapes;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array{name: \BackedEnum } | array{ name: string } $values
	 */
	public function sayHello(array $values): void
	{
		assertType('array{name: BackedEnum|string}', $values);
		if ($this->testShape($values)) {
			assertType('array{name: string}', $values);
		} else {
			assertType('array{name: BackedEnum}', $values);
		}
	}

	/**
	 * @param array{name: \BackedEnum } | array{ name: string } $values
	 * @phpstan-assert-if-true array{ name: string } $values
	 */
	private function testShape(array $values): bool
	{
		return true;
	}
}
