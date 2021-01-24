<?php

namespace MissingMethodReturnTypehint;

interface FooInterface
{

    public function getFoo($p1);

}

class FooParent
{

    public function getBar($p2)
    {

    }

}

class Foo extends FooParent implements FooInterface
{

    public function getFoo($p1)
    {

    }

    /**
     * @param $p2
     */
    public function getBar($p2)
    {

    }

    public function getBaz(): bool
    {
        return false;
    }

	/**
	 * @return \stdClass|array|int|null
	 */
	public function unionTypeWithUnknownArrayValueTypehint()
	{

	}

}

/**
 * @template T
 * @template U
 */
interface GenericInterface
{

}

class NonGenericClass
{

}

/**
 * @template A
 * @template B
 */
class GenericClass
{

}

class Bar
{

	public function returnsGenericInterface(): GenericInterface
	{

	}

	public function returnsNonGenericClass(): NonGenericClass
	{

	}

	public function returnsGenericClass(): GenericClass
	{

	}

}

class CallableSignature
{

	public function doFoo(): callable
	{

	}

}
