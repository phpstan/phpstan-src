<?php declare(strict_types = 1);

namespace Bug14440;

interface I {}

abstract class A
{
	/** @return class-string<static&I> */
	abstract public static function getCounterpartClass(): string;
}

final class ChildOne extends A implements I
{
	#[\Override]
	public static function getCounterpartClass(): string
	{
		return ChildTwo::class;
	}
}

final class ChildTwo extends A implements I
{
	#[\Override]
	public static function getCounterpartClass(): string
	{
		return ChildOne::class;
	}
}
