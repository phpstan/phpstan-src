<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug12131;

class Test
{
	/**
	 * @var non-empty-list<int>
	 */
	public array $array;

	public function __construct()
	{
		$this->array = array_fill(0, 10, 1);
	}

	public function setAtZero(): void
	{
		$this->array[0] = 1;
	}

	public function setAtOne(): void
	{
		$this->array[1] = 1;
	}

	public function setAtTwo(): void
	{
		$this->array[2] = 1;
	}
}
