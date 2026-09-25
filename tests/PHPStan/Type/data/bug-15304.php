<?php

namespace Bug15304;

use Bug15304\Models\ModelOne;
use Bug15304\Models\ModelTwo;
use Bug15304\Models\ModelThree;
use const Bug15304\Models\SOME_CONSTANT;

/**
 * @template T
 */
class Foo
{

	/** @return ModelOne */
	public function one()
	{
	}

	/** @return ModelTwo */
	public function two()
	{
	}

	/** @return ModelThree */
	public function three()
	{
	}

	/**
	 * @template U
	 * @param U $u
	 * @return T|U
	 */
	public function four($u)
	{
	}

	/** @return int */
	public function five()
	{
	}

}

use Bug15304\Models\ModelFour;

class Bar
{

	/** @return ModelFour */
	public function one()
	{
	}

	/** @return ModelOne */
	public function two()
	{
	}

}

namespace Bug15304\Other;

class Baz
{

	/** @return ModelOne */
	public function one()
	{
	}

}
