<?php // lint >= 8.2

namespace Bug13321b;

use function PHPStan\Testing\assertType;

class Foo
{
	public function __construct(public string $value)
	{
	}
}

readonly class Bar
{
	public function __construct(
		private ?Foo $foo,
	)
	{
	}

	public function bar(): void
	{
		if ($this->foo === null) {
			return;
		}
		if ($this->foo->value === '') {
			return;
		}

		assertType(Foo::class, $this->foo);
		assertType('non-empty-string', $this->foo->value);

		$test = function () {
			assertType(Foo::class, $this->foo);
			assertType('string', $this->foo->value);
		};

		$test();
	}

}
