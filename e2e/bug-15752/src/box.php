<?php

declare(strict_types=1);

interface Box
{
	/** @return list<string> */
	public function items(): array;
}

function box(): Box
{
	return new class(['a']) implements Box
	{
		/** @param list<string> $items */
		public function __construct(private array $items) {}

		public function items(): array
		{
			return $this->items;
		}
	};
}
