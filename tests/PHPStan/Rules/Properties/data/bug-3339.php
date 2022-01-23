<?php

namespace Bug3339;

class HelloWorld
{
	/** @var array{bool, bool, bool} */
	private $tuple;

	public function main(): void
	{
		$this->tuple = [true, true, true];

		for ($i = 0; $i < 3; ++$i)
		{
			$this->tuple[$i] = false;
		}
	}
}
