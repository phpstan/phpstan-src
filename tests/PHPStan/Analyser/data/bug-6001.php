<?php declare(strict_types=1);

namespace Bug6001;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(\Throwable $t): void
	{
		assertType('(int|string)', (new \Exception())->getCode());
		assertType('(int|string)', $t->getCode());
		assertType('int', (new \RuntimeException())->getCode());
		assertType('string', (new \PDOException())->getCode());
		assertType('int', (new MyException())->getCode());
		assertType('string', (new SubPDOException())->getCode());
	}

	/**
	 * @param \PDOException|MyException $exception
	 * @return void
	 */
	public function doBar($exception): void
	{
		assertType('int|string', $exception->getCode());
	}

}

class MyException extends \Exception
{

}

class SubPDOException extends \PDOException
{

}
