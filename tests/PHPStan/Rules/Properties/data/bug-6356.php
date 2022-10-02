<?php declare(strict_types=1);

namespace Bug6356;

class HelloWorld
{
	const ENUM_ZERO = 0;
	const ENUM_ONE = 1;
	const ENUM_TWO = 2;
	const ENUM_COUNT = 3;

	/** @var array<HelloWorld::*, array<int, bool>> */
	private $lists;

	public function main(): void
	{
		for ($type = 0; $type < self::ENUM_COUNT; ++$type)
		{
			$this->lists[$type][] = true;
		}

		print_r($this->lists);
	}
}
