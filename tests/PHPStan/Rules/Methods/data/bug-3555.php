<?php

namespace Bug3555;


class Enum
{
	const A = 1;
	const B = 2;
	const C = 3;
	const D = 4;
	const E = 5;
	const F = 6;
	const G = 7;
	const H = 8;
	const I = 9;

	/**
	 * @param Enum::* $arg
	 */
	public function run($arg): void
	{

	}

	public function doFoo()
	{
		$this->run(100);
	}

}
