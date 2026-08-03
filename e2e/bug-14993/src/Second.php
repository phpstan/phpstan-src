<?php

namespace Bug14993;

use Countable;

class Second implements Countable
{

	use CheckedTrait;

	public function count(): int
	{
		return 0;
	}

}
