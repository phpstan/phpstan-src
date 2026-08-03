<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsSeparate;

use Countable;

class SeparateFirst implements Countable
{

	use IgnoreErrorsSeparateFilesTrait;

	public function count(): int
	{
		return 0;
	}

}
