<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15228;

final class OnlyCoalesceAssigned
{
	private int|string|null $x = null;

	public function set(): void
	{
		$this->x ??= 1;
	}

	public function get(): int|string|null
	{
		return $this->x;
	}
}
