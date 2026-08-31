<?php declare(strict_types = 1);

namespace Bug15120b;

trait HasName
{

	public function name(): string
	{
		return 'name';
	}

}
