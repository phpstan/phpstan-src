<?php

namespace Bug10312b;

interface MinPhpVersionInterface
{
	/**
	 * @return PhpVersion::*
	 */
	public function provideMinPhpVersion() : int;
}

final class PhpVersion
{
	/**
	 * @var int
	 */
	public const PHP_52 = 50200;
	/**
	 * @var int
	 */
	public const PHP_53 = 50300;
	/**
	 * @var int
	 */
	public const PHP_54 = 50400;
	/**
	 * @var int
	 */
	public const PHP_55 = 50500;
}

final class TypedPropertyFromStrictConstructorReadonlyClassRector implements MinPhpVersionInterface
{
	public function provideMinPhpVersion(): int
	{
		return PhpVersion::PHP_55;
	}

}
