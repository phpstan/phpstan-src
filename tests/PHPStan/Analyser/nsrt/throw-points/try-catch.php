<?php

namespace ThrowPoints\TryCatch;

use PHPStan\TrinaryLogic;
use Throwable;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

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

	public static function createException(): MyInvalidArgumentException
	{

	}

	/**
	 * @throws MyRuntimeException
	 */
	public static function createExceptionOrThrow(): MyInvalidArgumentException
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
		maybeThrows();
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
		assertType('Throwable~InvalidArgumentException|RuntimeException', $e);
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

function (): void {
	try {
		if (Foo::myRand() === 0) {
			$foo = 1;
			throw Foo::createException();
		}

		if (Foo::myRand() === 1) {
			$bar = 1;
			throw Foo::createExceptionOrThrow();
		}
	} catch (MyInvalidArgumentException $e) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);
	} catch (\Throwable $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	}
};

function (): void {
	try {
		if (Foo::myRand() === 0) {
			$foo = 1;
			throw Foo::createException();
		}

		if (Foo::myRand() === 1) {
			$bar = 1;
			throw Foo::createExceptionOrThrow();
		}
	} catch (MyInvalidArgumentException $e) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);
	} catch (\Exception $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	}
};

function (): void {
	try {
		if (Foo::myRand() === 0) {
			$foo = 1;
			throw Foo::createException();
		}

		if (Foo::myRand() === 1) {
			$bar = 1;
			throw Foo::createExceptionOrThrow();
		}
	} catch (MyInvalidArgumentException $e) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);
	} catch (\Exception $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	} catch (\Throwable $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	}
};
