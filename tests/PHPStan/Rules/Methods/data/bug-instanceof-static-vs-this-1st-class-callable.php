<?php declare(strict_types=1); // lint >= 8.1

namespace BugInstanceofStaticVsThis1stClassCallable;

interface FooInterface
{
	public static function foo(): int;
}

class FooBase
{
	use FooTrait;

	public function bar(): void
	{
		if ($this instanceof FooInterface) {
			\PHPStan\Testing\assertType('int', (static::foo(...))());
			\PHPStan\Testing\assertType('int', ($this::foo(...))());
			\PHPStan\Testing\assertType('int', ($this->foo(...))());
		}

		if (is_a(static::class, FooInterface::class, true)) {
			\PHPStan\Testing\assertType('int', (static::foo(...))());
			\PHPStan\Testing\assertType('int', ($this::foo(...))());
			\PHPStan\Testing\assertType('int', ($this->foo(...))());
		}
	}
}
