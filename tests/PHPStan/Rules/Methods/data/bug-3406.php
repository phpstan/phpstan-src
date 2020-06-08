<?php

namespace Bug3406;

abstract class AbstractFoo
{

	public function myFoo(): void
	{

	}

	public function myBar(): void
	{

	}

}

trait TraitFoo
{

	abstract public function myFoo(): void;

	public function myBar(): void
	{

	}

}

final class ClassFoo extends AbstractFoo
{

	use TraitFoo;

}
