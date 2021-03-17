<?php

namespace Bug4288;

trait PaginationTrait
{

	/** @var int */
	private $test = self::DEFAULT_SIZE;

	private function paginate(int $size = self::DEFAULT_SIZE): void
	{
		echo $size;
	}
}

class MyClass
{
	use PaginationTrait;

	const DEFAULT_SIZE = 10;

	public function test(): void
	{
		$this->paginate();
	}
}

