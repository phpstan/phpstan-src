<?php declare(strict_types = 1);

namespace Bug14418Loops;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Service
{

	public function attempt(): string
	{
		return 'x';
	}

}

function retryEveryCatchContinues(Service $service): string
{
	for ($i = 0; $i < 3; $i++) {
		try {
			return $service->attempt();
		} catch (\Exception $e) {
			continue;
		}
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	throw $e;
}

function forContinue(): int
{
	for ($try = 0; $try <= 3; $try++) {
		if (rand(0, 1)) {
			$e = 1;
			continue;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function whileContinue(): int
{
	$i = 0;
	while ($i < 3) {
		$i++;
		if (rand(0, 1)) {
			$e = 1;
			continue;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function doWhileContinue(): int
{
	do {
		if (rand(0, 1)) {
			$e = 1;
			continue;
		}
		return 2;
	} while (rand(0, 1));
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

/**
 * @param list<int> $xs
 */
function foreachContinueMayNotIterate(array $xs): int
{
	foreach ($xs as $x) {
		if (rand(0, 1)) {
			$e = $x;
			continue;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $e);
	return 1;
}

function unrolledForeachContinue(): int
{
	foreach ([1, 2] as $x) {
		if (rand(0, 1)) {
			$e = $x;
			continue;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function unrolledForeachContinueOuterLoop(): int
{
	foreach ([1, 2, 3] as $a) {
		while (rand(0, 1)) {
			if (rand(0, 1)) {
				$e = $a;
				continue 2;
			}
			return 1;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function forBreak(): int
{
	for ($i = 0; $i < 3; $i++) {
		if (rand(0, 1)) {
			$e = 1;
			break;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function whileBreakMayNotIterate(int $n): int
{
	while ($n < 3) {
		if (rand(0, 1)) {
			$e = 1;
			break;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $e);
	return 1;
}

function whileTrueBreak(): int
{
	while (true) {
		if (rand(0, 1)) {
			$e = 1;
			break;
		}
		return 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function doWhileBreak(): int
{
	do {
		if (rand(0, 1)) {
			$e = 1;
			break;
		}
		return 2;
	} while (rand(0, 1));
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function continueOuterLoop(): int
{
	for ($i = 0; $i < 3; $i++) {
		while (true) {
			if (rand(0, 1)) {
				$e = 1;
				continue 2;
			}
			return 2;
		}
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function continueFromSwitch(): int
{
	for ($i = 0; $i < 3; $i++) {
		switch (rand(0, 1)) {
			case 0:
				$e = 1;
				continue 2;
			default:
				return 2;
		}
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	return $e;
}

function catchContinueWithFinally(): int
{
	for ($i = 0; $i < 3; $i++) {
		try {
			if (rand(0, 1)) {
				return 1;
			}
			throw new \Exception();
		} catch (\Exception $e) {
			continue;
		} finally {
			echo 'x';
		}
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $e);
	throw $e;
}

function endReachable(): void
{
	for ($i = 0; $i < 3; $i++) {
		if (rand(0, 1)) {
			$e = 1;
			continue;
		}
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $e);
}

function continueTypeAfterLoop(): void
{
	$v = 'str';
	for ($try = 0; $try <= 3; $try++) {
		if (rand(0, 1)) {
			$v = 5;
			continue;
		}
		return;
	}
	assertType('5', $v);
}

function loopHeadFromContinue(): void
{
	$v = 'init';
	for ($i = 0; $i < 3; $i++) {
		assertType("1|'init'", $v);
		if (rand(0, 1)) {
			$v = 1;
			continue;
		}
		return;
	}
	assertType('1', $v);
}
