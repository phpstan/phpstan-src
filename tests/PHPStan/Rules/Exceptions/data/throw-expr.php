<?php

namespace ThrowExpr;

class Bar
{

	public function doFoo(bool $b): void
	{
		$b ? true : throw new \Exception();

		throw new \Exception();
	}

}
