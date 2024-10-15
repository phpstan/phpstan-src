<?php

namespace Generics\Bug9630;

/**
 * @template T of A
 */
interface A
{
	/**
	 * @return T
	 */
	public function getOther(): A;
}

/**
 * @implements A<A2>
 */
class A1 implements A
{
	public function getOther(): A2
	{
		return new A2();
	}
}

/**
 * @implements A<A1>
 */
class A2 implements A
{
	public function getOther(): A1
	{
		return new A1();
	}
}

/**
 * @template T of A
 */
interface B
{
	/**
	 * @return T|null
	 */
	public function f(): ?A;
}

