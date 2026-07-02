<?php declare(strict_types = 1);

namespace Bug11100;

final class HelloWorld
{

	/**
	 * @param array<int> $numbers
	 * @return array<int>
	 * @phpstan-pure
	 */
	public function double(array $numbers): array
	{
		return array_map(static fn (int $x): int => $x * 2, $numbers);
	}

}
