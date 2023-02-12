<?php

namespace StaticMethodsTemplateTypes;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Foo
{

	/**
	 * @template U
	 * @param T $t
	 * @return T
	 */
	public static function bar($t)
	{
	}

	/**
	 * @template U
	 * @param U $u
	 * @return U
	 */
	public static function baz($u)
	{
	}

	/**
	 * @return T
	 */
	public static function qux()
	{
	}

	public static function doFoo()
	{
		assertType('StaticMethodsTemplateTypes\T', self::bar(42));
		assertType('int', self::baz(42));
		assertType('StaticMethodsTemplateTypes\T', self::qux());
	}

}

assertType('StaticMethodsTemplateTypes\T', Foo::bar(42));
assertType('int', Foo::baz(42));
assertType('StaticMethodsTemplateTypes\T', Foo::qux());
