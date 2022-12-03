<?php

namespace Bug4288b;

trait PaginationTrait
{

	/** @var int */
	private $test = MyClass::DEFAULT_SIZE;

	private function paginate(int $size = MyClass::DEFAULT_SIZE): void
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

