<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14880Property;

final class KeepSelfClassStaticProperty
{

	private static string $foo = 'bar';

	public function run(): string
	{
		self::class::$foo = 'baz';
		return self::class::$foo;
	}

}

final class KeepClassStringStaticProperty
{

	private static string $foo = 'bar';

	public function run(): string
	{
		$class = self::class;
		$class::$foo = 'baz';
		return $class::$foo;
	}

}

final class KeepGenericClassStringStaticProperty
{

	private static string $foo = 'bar';

	public function run(): string
	{
		/** @var class-string<self> $class */
		$class = self::class;
		$class::$foo = 'baz';
		return $class::$foo;
	}

}
