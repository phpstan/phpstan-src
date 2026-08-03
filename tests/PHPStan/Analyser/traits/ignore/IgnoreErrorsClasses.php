<?php declare(strict_types = 1);

namespace TraitsIgnoreErrors;

use Countable;

class First implements Countable
{

	use IgnoreErrorsTrait;

	public function count(): int
	{
		return 0;
	}

}

class Second implements Countable
{

	use IgnoreErrorsTrait {
		check as checkSecond;
	}

	public function count(): int
	{
		return 0;
	}

}
