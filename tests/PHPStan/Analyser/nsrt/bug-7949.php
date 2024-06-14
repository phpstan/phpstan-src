<?php declare(strict_types = 1);

namespace Bug7949;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function bar(?string $price): void
	{
		$price = strlen($price ?? '') > 0 ? $price : '0';
		assertType('non-empty-string', $price);

		$this->foo($price);
	}

	public function foo(string $test): void {
	}
}
