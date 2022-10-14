<?php

namespace DeadCatchMagicMethods;

/**
 * @method void magicMethod1();
 */
class ClassWithMagicMethod
{
	/**
	 * @throws void
	 */
	public function __call($name, $arguments)
	{
	}

	public function test()
	{
		try {
			(new ClassWithMagicMethod())->magicMethod1();
		} catch (\Exception $e) {

		}
	}
}

/**
 * @method void magicMethod2();
 */
class ClassWithMagicMethod2
{
	/**
	 * @throws \Exception
	 */
	public function __call($name, $arguments)
	{
		throw new \Exception();
	}

	public function test()
	{
		try {
			(new ClassWithMagicMethod2())->magicMethod2();
		} catch (\Exception $e) {

		}
	}
}

/**
 * @method void magicMethod3();
 */
class ClassWithMagicMethod3
{
	public function test()
	{
		try {
			(new ClassWithMagicMethod())->magicMethod3();
		} catch (\Exception $e) {
			// No error since `implicitThrows: true` is used by default
		}
	}
}
