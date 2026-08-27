<?php declare(strict_types = 1);

namespace Bug15120;

class Consumer
{

	/** @return non-empty-string */
	public function describe(Foo $foo): string
	{
		return $foo->get();
	}

}
