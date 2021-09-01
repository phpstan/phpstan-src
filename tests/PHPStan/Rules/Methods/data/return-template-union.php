<?php

namespace ReturnTemplateUnion;

class Foo
{

	/**
	 * @template T of bool|string|int|float
	 * @param T $p
	 * @return T
	 */
	public function doFoo($p)
	{
		return $p;
	}

	/**
	 * @template T of bool|string|int|float
	 * @param T|null $p
	 * @return T
	 */
	public function doFoo2($p)
	{
		return $p;
	}

	/**
	 * @template T of bool|string|int|float
	 * @param T|null $p
	 * @return T|null
	 */
	public function doFoo3($p)
	{
		return $p;
	}

}
