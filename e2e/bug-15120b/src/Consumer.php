<?php declare(strict_types = 1);

namespace Bug15120b;

class Consumer
{

	use HasName;

	/** @return non-empty-string */
	public function describe(Foo $foo): string
	{
		return $foo->get();
	}

}
