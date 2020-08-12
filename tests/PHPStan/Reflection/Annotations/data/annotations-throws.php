<?php

namespace ThrowsAnnotations;

function withoutThrows()
{

}

/**
 * @throws \RuntimeException
 */
function throwsRuntime()
{

}

class Foo
{

	public function withoutThrows()
	{

	}

	/**
	 * @throws \RuntimeException
	 */
	public function throwsRuntime()
	{

	}

	/**
	 * @throws \RuntimeException
	 */
	public static function staticThrowsRuntime()
	{

	}

}

class PhpstanFoo
{
	/**
	 * @throws \RuntimeException
	 *
	 * @phpstan-throws void
	 */
	public function withoutThrows()
	{

	}

	/**
	 * @throws \Exception
	 *
	 * @phpstan-throws \RuntimeException
	 */
	public function throwsRuntime()
	{

	}

	/**
	 * @throws \Exception
	 *
	 * @phpstan-throws \RuntimeException
	 */
	public static function staticThrowsRuntime()
	{

	}

}

/**
 * @method void withoutThrows()
 * @method void throwsRuntime()
 * @method void staticThrowsRuntime()
 */
class FooWithMethodAnnotation extends Foo
{

}

interface FooInterface
{

	public function withoutThrows();

	/**
	 * @throws \RuntimeException
	 */
	public function throwsRuntime();

	/**
	 * @throws \RuntimeException
	 */
	public static function staticThrowsRuntime();

}

trait FooTrait
{

	public function withoutThrows()
	{

	}

	/**
	 * @throws \RuntimeException
	 */
	public function throwsRuntime()
	{

	}

	/**
	 * @throws \RuntimeException
	 */
	public static function staticThrowsRuntime()
	{

	}

}

trait BarTrait
{

	use FooTrait;

}
