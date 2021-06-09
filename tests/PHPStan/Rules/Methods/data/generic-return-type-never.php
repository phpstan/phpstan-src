<?php

namespace GenericReturnTypeNever;

interface Lorem
{

}

interface Ipsum
{

}

class Dolor
{

}

class Sit
{

}

class Foo
{

	/**
	 * @template T
	 * @param T $p
	 * @return T&Lorem
	 */
	public function doFoo($p)
	{

	}

	/**
	 * @template T
	 * @param T $p
	 * @return T&Dolor
	 */
	public function doBar($p)
	{

	}

	/**
	 * @return Sit&Dolor
	 */
	public function doBaz()
	{

	}

	/**
	 * @template T
	 * @param T $p
	 * @return array<T&Dolor>
	 */
	public function doBazBaz($p)
	{

	}

	public function doTest(Ipsum $ipsum): void
	{
		$this->doFoo($ipsum); // OK
		$this->doBar($ipsum); // OK
		$this->doBar(new Sit()); // error
		$this->doBaz(); // OK
		$this->doBazBaz($ipsum); // OK
		$this->doBazBaz(new Sit()); // error
	}

}
