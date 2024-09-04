<?php declare(strict_types = 1);

namespace Bug6107;

class Test
{
	private ?\stdClass $item;

	public function __construct(?\stdClass $item)
	{
		$this->item = $item;
	}

	public function handle(): void
	{
		$value = $this->item->value ?? 'custom value';
	}
}
