<?php declare(strict_types=1);

namespace ForLoopNoScopePollution;


use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class ForLoop
{

	/** @param int $b */
	public function loopThatIteratesAtLeastOnce(int $a, $b): void
	{
		$j = 0;
		for ($i = 0, $j = 10; $i < 10; $i++, $j--) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType('int<10, max>', $i);
		assertNativeType('int<10, max>', $i);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $i);

		assertType('int<min, 10>', $j);
		assertNativeType('int<min, 10>', $j);
		assertVariableCertainty(TrinaryLogic::createYes(), $j);

		assertType('int<0, 1>', $a);
		assertNativeType('int', $a);
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType('int<0, 1>', $b);
		assertNativeType('int', $b);
		assertVariableCertainty(TrinaryLogic::createYes(), $b);

		assertType('int<0, 1>', $c);
		assertNativeType('int', $c);
		assertVariableCertainty(TrinaryLogic::createYes(), $c);
	}

	/** @param int $b */
	public function loopThatMightIterateAtLeastOnce(int $a, $b): void
	{
		$j = 0;
		for ($i = 0, $j = 10; $i < rand(0, 1); $i++, $j--) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType('int<0, max>', $i);
		assertNativeType('int<0, max>', $i);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $i);

		assertType('int<min, 10>', $j);
		assertNativeType('int<min, 10>', $j);
		assertVariableCertainty(TrinaryLogic::createYes(), $j);

		assertType('int', $a);
		assertNativeType('int', $a);
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType('int', $b);
		assertNativeType('mixed', $b);
		assertVariableCertainty(TrinaryLogic::createYes(), $b);

		assertType('int<0, 1>', $c);
		assertNativeType('int', $c);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $c);
	}

	/** @param int $b */
	public function loopThatNeverIterates(int $a, $b): void
	{
		$j = 0;
		for ($i = 0, $j = 10; $i > 10; $i++, $j--) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType('*ERROR*', $i);
		assertNativeType('*ERROR*', $i);
		assertVariableCertainty(TrinaryLogic::createNo(), $i);

		assertType('0', $j);
		assertNativeType('0', $j);
		assertVariableCertainty(TrinaryLogic::createYes(), $j);

		assertType('int', $a);
		assertNativeType('int', $a);
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType('int', $b);
		assertNativeType('mixed', $b);
		assertVariableCertainty(TrinaryLogic::createYes(), $b);

		assertType('*ERROR*', $c);
		assertNativeType('*ERROR*', $c);
		assertVariableCertainty(TrinaryLogic::createNo(), $c);
	}

}
