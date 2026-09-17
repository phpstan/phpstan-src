<?php declare(strict_types = 1);

namespace Bug15252;

class InvalidName
{

	public function name(): string
	{
		$self = new \self();

		return 'invalid';
	}

}
