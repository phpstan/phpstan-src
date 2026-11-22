<?php declare(strict_types = 1);

namespace Bug15375;

trait HasName
{

	public function name(): string
	{
		return 'name';
	}

}
