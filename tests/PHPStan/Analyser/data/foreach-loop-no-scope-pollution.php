<?php declare(strict_types=1);

namespace ForEachLoopNoScopePollution;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class ForEachLoopNoScopePollution
{

	/** @param int $b */
	public function loopThatIteratesAtLeastOnce(int $a, $b): void
	{
		$items = [17 => 'foo', 'bar' => 19];

		foreach ($items as $key => $item) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType("17|'bar'", $key);
		assertNativeType("17|'bar'", $key);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $key);

		assertType("19|'foo'", $item);
		assertNativeType("19|'foo'", $item);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $item);

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
		$items = [];
		if (rand(0, 1)) {
			$items[17] = 'foo';
		}
		if (rand(0, 1)) {
			$items['bar'] = 19;
		}

		foreach ($items as $key => $item) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType("17|'bar'", $key);
		assertNativeType("17|'bar'", $key);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $key);

		assertType("19|'foo'", $item);
		assertNativeType("19|'foo'", $item);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $item);

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
		$items = [];

		foreach ($items as $key => $item) {
			$a = rand(0, 1);
			$b = rand(0, 1);
			$c = rand(0, 1);
		}

		assertType('*ERROR*', $key);
		assertNativeType('*ERROR*', $key);
		assertVariableCertainty(TrinaryLogic::createNo(), $key);

		assertType('*ERROR*', $item);
		assertNativeType('*ERROR*', $item);
		assertVariableCertainty(TrinaryLogic::createNo(), $item);

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
