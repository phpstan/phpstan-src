<?php declare(strict_types = 1);

namespace Bug15120b;

class Entity
{

	public string $name = 'entity';

	/** @return non-empty-string */
	public function describe(Foo $foo): string
	{
		return $foo->get();
	}

}
