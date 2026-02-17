<?php declare(strict_types = 1);

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
}
