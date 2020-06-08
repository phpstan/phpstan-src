<?php

namespace Bug3406_2;

trait AbstractTrait
{

	abstract public function test(): void;

}

trait ImplTrait
{

	public function test(): void
	{

	}

}

class Test
{

	use AbstractTrait;
	use ImplTrait;

}

class Test2
{

	use ImplTrait;
	use AbstractTrait;

}
