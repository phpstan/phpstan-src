<?php

declare(strict_types=1);

namespace Bug9391;

class A
{
	private $a;

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array
	{
		return [
			'a' => 1,
		];
	}

	/**
	 * @return array<int, string>
	 */
	public function __serialize(): array
	{
		return [
			'a' => $this->a
		];
	}
}

class B extends A
{
	private $b;

	public function __debugInfo(): array
	{
		return [
			'b' => 2,
		];
	}

	public function __serialize(): array
	{
		return [
			'b' => $this->b
		];
	}

}
