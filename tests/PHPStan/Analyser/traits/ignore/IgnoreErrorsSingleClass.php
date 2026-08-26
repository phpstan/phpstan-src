<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsSingle;

use Countable;

class Only implements Countable
{

	use IgnoreErrorsSingleTrait;

	public function count(): int
	{
		return 0;
	}

}
