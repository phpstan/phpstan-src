<?php

namespace ThrowPoints\Bug9349;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{
	/**
	 * @throws \RuntimeException
	 */
	public function throwsRuntime(): void
	{
	}

	/**
	 * @throws \LogicException
	 */
	public function throwsLogic(): void
	{
	}

	/** @return mixed */
	public function doSomething(): void
	{
	}
}

function (Foo $foo): void {
	try {
		$foo->throwsRuntime();
		$sql = 'SELECT * FROM foo';
		$foo->doSomething();
	} catch (\PDOException $e) {
		// throwsRuntime() declares @throws RuntimeException
		// PDOException extends RuntimeException, so throwsRuntime()
		// might throw a PDOException before $sql is assigned.
		// But doSomething() also has implicit throws after $sql is assigned.
		// So $sql might or might not be defined.
		assertVariableCertainty(TrinaryLogic::createMaybe(), $sql);
	}
};

function (Foo $foo): void {
	try {
		$foo->throwsLogic();
		$sql = 'SELECT * FROM foo';
		$foo->doSomething();
	} catch (\PDOException $e) {
		// LogicException and PDOException are unrelated
		// Only implicit throws from doSomething() can reach here
		// $sql is assigned before doSomething()
		assertVariableCertainty(TrinaryLogic::createYes(), $sql);
	}
};
