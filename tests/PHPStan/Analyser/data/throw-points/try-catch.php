<?php

namespace ThrowPoints\TryCatch;

use PHPStan\TrinaryLogic;
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
	public static function createInvalidArgumentException(): \InvalidArgumentException
	{

	}

	/** @throws void */
	public static function createMyInvalidArgumentException(): MyInvalidArgumentException
	{

	}

	/** @throws void */
	public static function createRuntimeException(): \RuntimeException
	{

	}

	/** @throws void */
	public static function createMyRuntimeException(): MyRuntimeException
	{

	}

	/** @throws void */
	public static function myRand(): int
	{

	}

}

function (): void {
	try {
		if (Foo::myRand() === 0) {
			$foo = 1;
			throw Foo::createInvalidArgumentException();
		}
		if (Foo::myRand() === 1) {
			$foo = 2;
			throw Foo::createMyInvalidArgumentException();
		}

		if (Foo::myRand() === 2) {
			$baz = 1;
			throw Foo::createRuntimeException();
		}
		if (Foo::myRand() === 3) {
			$baz = 2;
			throw Foo::createMyRuntimeException();
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
