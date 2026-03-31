<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13735;

use function PHPStan\Testing\assertType;

class Bug13735Test
{
	private ?Foo $foo = null;

	public function testFoo(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735\Foo', $this->foo);
		self::assertTrue(true);
		assertType('Bug13735\Foo', $this->foo);
	}

	public static function assertTrue(mixed $condition, string $message = ''): void
	{

	}
}

class Foo {
	public function doSomething(): bool {
		return true;
	}
}
