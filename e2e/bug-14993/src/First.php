<?php

namespace Bug14993;

use Countable;

class First implements Countable
{

	use CheckedTrait;

	public function count(): int
	{
		return 0;
	}

}
