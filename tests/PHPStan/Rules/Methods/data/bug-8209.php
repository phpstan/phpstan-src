<?php declare(strict_types=1);

namespace Bug8209;

class HelloWorld
{
	public function test1(?int $id): int
	{
		return $id;
	}

	/**
	 * @return array<int>
	 */
	public function test2(?int $id): array
	{
		return [$id];
	}
}
