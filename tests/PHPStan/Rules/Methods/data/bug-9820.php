<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9820;

class HelloWorld
{
	/**
	 * @return $this
	 */
	public function x(): static
	{
		return $this;
	}

	public function test(): void
	{
		$this
			->x(1);
	}

	public function test2(): void
	{
		$this
			->x()
			->x(1);
	}

	public function test3(?self $selfOrNull): void
	{
		$selfOrNull
			?->x(1);
	}

	public function test4(?self $selfOrNull): void
	{
		$selfOrNull
			?->x()
			?->x(1);
	}
}
