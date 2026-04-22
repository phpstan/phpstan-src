<?php declare(strict_types = 1);

namespace Bug12434;

use BackedEnum;
use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param non-empty-list<array{name: BackedEnum}>|non-empty-list<array{name: string}> $values
	 */
	public function sayHello(array $values): void
	{
		assertType('non-empty-list<array{name: BackedEnum}>|non-empty-list<array{name: string}>', $values);
		if ($this->testShape($values)) {
			assertType('non-empty-list<array{name: string}>', $values);
		} else {
			assertType('non-empty-list<array{name: BackedEnum}>', $values);
		}
	}

	/**
	 * @param non-empty-list<array{name: BackedEnum}>|non-empty-list<array{name: string}> $values
	 * @phpstan-assert-if-true non-empty-list<array{name: string}> $values
	 */
	private function testShape(array $values): bool
	{
		return true;
	}

}
