<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsNested;

use Countable;

class NestedFirst implements Countable
{

	use IgnoreErrorsNestedTrait;

	public function count(): int
	{
		return 0;
	}

}

class NestedSecond implements Countable
{

	use IgnoreErrorsNestedTrait;

	public function count(): int
	{
		return 0;
	}

}
