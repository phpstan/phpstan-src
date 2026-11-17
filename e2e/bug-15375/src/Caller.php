<?php declare(strict_types = 1);

namespace Bug15375;

class Caller
{

	public function name(): string
	{
		return (new Later())->name();
	}

}
