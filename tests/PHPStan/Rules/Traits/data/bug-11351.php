<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug11351;

trait TA
{
	protected const array A = [
		self::CA => 'CA',
	];
}

class A
{
	use TA;

	public const CA = 'abc';

	protected const array A = [
		self::CA => 'CA',
	];
}

trait TB
{
	protected const array B = [
		'key' => self::CB,
	];
}

class B
{
	use TB;

	public const CB = 'xyz';

	protected const array B = [
		'key' => self::CB,
	];
}
