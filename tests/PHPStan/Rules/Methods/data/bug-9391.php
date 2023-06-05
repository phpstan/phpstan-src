<?php

declare(strict_types=1);

namespace Bug9391;

class A
{
	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array
	{
		return [
			'a' => 1,
		];
	}
}

class B extends A
{
	public function __debugInfo(): array
	{
		return [
			'b' => 2,
		];
	}
}
