<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15228TooWide;

class Foo
{
	private int|string|null $x = null;

	public function doFoo(): void
	{
		$this->x ??= 1;
	}

	public function getX(): int|string|null
	{
		return $this->x;
	}
}
