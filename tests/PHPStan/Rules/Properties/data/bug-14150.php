<?php declare(strict_types = 1);

namespace Bug14150Properties;

class HelloWorld
{
	public int $x = 5;

	/**
	 * @return $this
	 */
	public function x(): static
	{
		return $this;
	}

	public function test3(): void
	{
		$this
			->x = null;
	}

	public function test4(): void
	{
		$this
			->x()
			->x = null;
	}
}
