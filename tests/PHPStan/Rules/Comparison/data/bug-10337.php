<?php declare(strict_types=1);

namespace Bug10337;

use function PHPStan\Testing\assertType;

class App
{
	/**
	 * @return ($calledFromShutdownHandler is true ? void : never)
	 */
	public function callExit(bool $calledFromShutdownHandler = false): void
	{
		// run before shutdown code here

		if (!$calledFromShutdownHandler) {
			exit;
		}
	}

	public function testOnlyVoid(): void
	{
		(new App())->callExit(true);
	}

	/**
	 * @return never
	 */
	public function testVoidAndNever(): void
	{
		$app = new App();
		assertType('null', $app->callExit(true));
		assertType('never', $app->callExit(false));
	}

	/**
	 * @return never
	 */
	public function testVoidAndNever2(): void
	{
		$app = new class() extends App {
		};
		assertType('null', $app->callExit(true));
		assertType('never', $app->callExit(false));
	}

	/**
	 * @return never
	 */
	public function testVoidAndNever3(): void
	{
		$app = new class() extends App {
			#[\Override]
			public function callExit(bool $calledFromShutdownHandler = false): void
			{
				parent::callExit($calledFromShutdownHandler);
			}
		};
		assertType('null', $app->callExit(true));
		assertType('never', $app->callExit(false));
	}
}
