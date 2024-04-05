<?php

namespace StaticMethodCallStatementNoSideEffects;

class BzzStatic
{
	static function regular(string $a): string
	{
		return $a;
	}

	/**
	 * @phpstan-pure
	 */
	static function pure1(string $a): string
	{
		return $a;
	}

	/**
	 * @psalm-pure
	 */
	static function pure2(string $a): string
	{
		return $a;
	}

	/**
	 * @pure
	 */
	static function pure3(string $a): string
	{
		return $a;
	}

	/**
	 * @phan-pure
	 */
	static function pure4(string $a): string
	{
		return $a;
	}

	/**
	 * @phan-side-effect-free
	 */
	static function pure5(string $a): string
	{
		return $a;
	}
}

function(): void {
	BzzStatic::regular('test');
	BzzStatic::pure1('test');
	BzzStatic::pure2('test');
	BzzStatic::pure3('test');
	BzzStatic::pure4('test');
	BzzStatic::pure5('test');
};

class PureThrows
{

	/**
	 * @phpstan-pure
	 * @throws void
	 */
	public static function pureAndThrowsVoid()
	{

	}

	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public static function pureAndThrowsException()
	{

	}

	public function doFoo(): void
	{
		self::pureAndThrowsVoid();
		self::pureAndThrowsException();
	}

}
