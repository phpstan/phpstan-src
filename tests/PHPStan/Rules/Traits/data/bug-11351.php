<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug11351;

trait TA
{
	protected const A = [
		self::CA => 'CA',
	];
}

class A
{
	use TA;
	public const CA = 'abc';
}

trait TB
{
	protected const B = [
		'key' => self::CB,
	];
}

class B
{
	use TB;
	public const CB = 'value';
}

trait TC
{
	protected const C = [
		self::CC1 => self::CC2,
	];
}

class C
{
	use TC;
	public const CC1 = 'key';
	public const CC2 = 'value';
}
