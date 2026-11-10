<?php declare(strict_types = 1);

namespace Bug14993;

class First implements \Countable
{
	use CheckedTrait;

	#[\Override]
	public function count(): int
	{
		return 0;
	}
}

class Second implements \Countable
{
	use CheckedTrait;

	#[\Override]
	public function count(): int
	{
		return 0;
	}
}
