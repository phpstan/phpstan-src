<?php

namespace Bug13321;

use function PHPStan\Testing\assertType;

class Foo
{
	public function __construct(public readonly string $value)
	{
	}
}

class Bar
{
	public function __construct(
		private readonly ?Foo $foo,
		private ?Foo $writableFoo = null,
	)
	{
	}

	public function bar(): void
	{
		if ($this->foo === null) {
			return;
		}
		if ($this->writableFoo === null) {
			return;
		}

		$test = function () {
			assertType(Foo::class, $this->foo);
			assertType(Foo::class.'|null', $this->writableFoo);

			echo $this->foo->value;
		};

		$test();
	}

}
