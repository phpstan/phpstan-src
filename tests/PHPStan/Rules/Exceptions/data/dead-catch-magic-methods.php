<?php

namespace DeadCatchMagicMethods;

/**
 * @method void magicMethod1();
 * @method static void staticMagicMethod1();
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

		try {
			ClassWithMagicMethod::staticMagicMethod1();
		} catch (\Exception $e) {
			// No error since `implicitThrows: true` is used by default
		}
	}
}

/**
 * @method void magicMethod2();
 * @method static void staticMagicMethod2();
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

	/**
	 * @throws void
	 */
	public static function __callStatic($name, $arguments)
	{
	}

	public function test()
	{
		try {
			(new ClassWithMagicMethod2())->magicMethod2();
		} catch (\Exception $e) {

		}

		try {
			ClassWithMagicMethod2::staticMagicMethod2();
		} catch (\Exception $e) {

		}
	}
}
