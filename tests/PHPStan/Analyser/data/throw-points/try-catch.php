<?php

namespace ThrowPoints\TryCatch;

use PHPStan\TrinaryLogic;
use Throwable;
use function PHPStan\Analyser\assertType;
use function PHPStan\Analyser\assertVariableCertainty;

class MyInvalidArgumentException extends \InvalidArgumentException
{

}

class MyRuntimeException extends \RuntimeException
{

}

class Foo
{

	/** @throws void */
	public static function myRand(): int
	{

	}

}

function (): void {
	try {
		if (Foo::myRand() === 0) {
			$foo = 1;
			throw new \InvalidArgumentException();
		}
		if (Foo::myRand() === 1) {
			$foo = 2;
			throw new MyInvalidArgumentException();
		}

		if (Foo::myRand() === 2) {
			$baz = 1;
			throw new \RuntimeException();
		}
		if (Foo::myRand() === 3) {
			$baz = 2;
			throw new MyRuntimeException();
		}

		$bar = 1;
	} catch (\InvalidArgumentException $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertType('1|2', $foo);

		assertVariableCertainty(TrinaryLogic::createNo(), $bar);
		assertVariableCertainty(TrinaryLogic::createNo(), $baz);
	} catch (\RuntimeException $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createNo(), $bar);
		assertVariableCertainty(TrinaryLogic::createYes(), $baz);
		assertType('1|2', $baz);
	} catch (\Throwable $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
		assertVariableCertainty(TrinaryLogic::createNo(), $baz);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('1|2', $foo);

		assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);
		assertType('1', $bar);

		assertVariableCertainty(TrinaryLogic::createMaybe(), $baz);
		assertType('1|2', $baz);
	}
};

function (): void {
	try {
		maybeThrows();
		$foo = 1;
		throw new \InvalidArgumentException();
	} catch (\InvalidArgumentException $e) {
		assertType('1', $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function (): void {
	try {
		$foo = new Foo();
	} catch (Throwable $e) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function (): void {
	try {
		$foo = new \InvalidArgumentException();
	} catch (Throwable $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};
