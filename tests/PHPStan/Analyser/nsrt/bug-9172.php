<?php

namespace Bug9172;

use function PHPStan\Testing\assertType;

final class HelloWorld
{
	/** @var int<0, self::MAX_DEPOSIT> */
	public const MIN_DEPOSIT = 1_000;

	/** @var int<self::MIN_DEPOSIT, max> */
	public const MAX_DEPOSIT = 20_000;
}

abstract class AbstractDeposit
{
	/** @var int<0, self::MAX_DEPOSIT> */
	public const MIN_DEPOSIT = 1_000;

	/** @var int<self::MIN_DEPOSIT, max> */
	public const MAX_DEPOSIT = 20_000;

	public function test(): void
	{
		assertType('int<0, 20000>', static::MIN_DEPOSIT);
		assertType('int<1000, max>', static::MAX_DEPOSIT);
	}
}

final class DepositService
{
	/** @param int<HelloWorld::MIN_DEPOSIT, HelloWorld::MAX_DEPOSIT> $amount */
	public function deposit(int $amount): void
	{
		assertType('int<1000, 20000>', $amount);
	}
}

function test(): void {
	assertType('1000', HelloWorld::MIN_DEPOSIT);
	assertType('20000', HelloWorld::MAX_DEPOSIT);
}
