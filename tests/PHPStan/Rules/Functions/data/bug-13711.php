<?php declare(strict_types = 1);

namespace Bug13711;

class Foo
{
	/** @var array<string> */
	private array $array = [];

	/** @return array<string> */
	public function &getList(): array
	{
		return $this->array;
	}

	public function rewind(): void
	{
		reset($this->getList());
	}
}
