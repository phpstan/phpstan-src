<?php

namespace Bug9172Integration;

final class HelloWorld
{
	/** @var int<0, self::MAX_DEPOSIT> */
	public const MIN_DEPOSIT = 1_000;

	/** @var int<self::MIN_DEPOSIT, max> */
	public const MAX_DEPOSIT = 20_000;

	/** @param int<self::MIN_DEPOSIT, self::MAX_DEPOSIT> $amount */
	public function deposit(int $amount): void
	{
	}
}

final class CircularValues
{
	/** @var int<0, self::MAX> */
	public const MIN = self::MAX - 19_000;

	/** @var int<self::MIN, max> */
	public const MAX = self::MIN + 19_000;
}
