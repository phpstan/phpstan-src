<?php

namespace Bug13735;

use function PHPStan\Testing\assertType;
use function rand;

class Bug13735Test
{
	private ?Foo $foo = null;
	public static ?Foo $staticFoo = null;

	public function testFoo(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735\Foo', $this->foo);
		self::assertTrue(true);
		assertType('Bug13735\Foo', $this->foo);

		assertType('bool', $this->foo->aBool);
		$this->foo->aBool = true;
		assertType('true', $this->foo->aBool);
		self::assertTrue(true);
		assertType('true', $this->foo->aBool);
	}

	public function testCallFoo(): void
	{
		if ($this->getFoo() === null) {
			return;
		}

		// the getFoo() method could reference a static property in its body,
		// so self::assertTrue() still needs to invalidate $this->getFoo().
		assertType('Bug13735\Foo', $this->getFoo());
		self::assertTrue(true);
		assertType('Bug13735\Foo|null', $this->getFoo());
	}

	public function testStaticFoo(): void
	{
		self::$staticFoo = new Foo();
		assertType('Bug13735\Foo', self::$staticFoo);
		self::assertTrue(true);
		assertType('Bug13735\Foo|null', self::$staticFoo);
	}

	public static function assertTrue(mixed $condition, string $message = ''): void
	{
	}

	public function getFoo(): ?Foo {
		return rand(0 ,1) ? null : new Foo();
	}
}

class Foo {
	public bool $aBool = false;
}
