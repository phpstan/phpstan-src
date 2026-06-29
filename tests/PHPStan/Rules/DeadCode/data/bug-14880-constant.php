<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14880Constant;

final class KeepSelfClassConstant
{

	private const FOO = 'bar';

	public function run(): string
	{
		return self::class::FOO;
	}

}

final class KeepClassStringConstant
{

	private const FOO = 'bar';

	public function run(): string
	{
		$class = self::class;
		return $class::FOO;
	}

}

final class KeepGenericClassStringConstant
{

	private const FOO = 'bar';

	public function run(): string
	{
		/** @var class-string<self> $class */
		$class = self::class;
		return $class::FOO;
	}

}
