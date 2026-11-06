<?php // lint >= 8.1

declare(strict_types = 1);

namespace ReadonlyPropertyPhpVersionRange;

use function PHPStan\Testing\assertType;

class Foo
{

	public function __construct(
		public readonly int|string $a,
		public int|string $b,
	)
	{
	}

	public function doFoo(): void
	{
		if (!is_int($this->a) || !is_int($this->b)) {
			return;
		}

		function () {
			assertType('int', $this->a);
			assertType('int|string', $this->b);
		};

		$this->impure();
		assertType('int', $this->a);
		assertType('int|string', $this->b);
	}

	public function impure(): void
	{
	}

}
