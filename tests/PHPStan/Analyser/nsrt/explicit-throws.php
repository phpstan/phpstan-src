<?php

namespace ExplicitThrows;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(): void
	{
		try {
			doFoo();
			$a = 1;
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doBar(): void
	{
		try {
			doFoo();
			$a = 1;
			$this->throwInvalidArgument();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
		}
	}

	public function doBaz(): void
	{
		try {
			doFoo();
			$a = 1;
			$this->throwInvalidArgument();
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
		}
	}

	public function doThrowInArrowFunctionArg(): void
	{
		try {
			doFoo();
			$a = 1;
			array_map(static fn (int $i) => throw new \InvalidArgumentException(), [1, 2]);
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doThrowInClosureArg(): void
	{
		try {
			doFoo();
			$a = 1;
			array_map(static function (int $i): int {
				throw new \InvalidArgumentException();
			}, [1, 2]);
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doThrowInCalledClosure(): void
	{
		$callback = static function (): void {
			throw new \InvalidArgumentException();
		};
		try {
			doFoo();
			$a = 1;
			$callback();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doThrowInImmediatelyInvokedClosure(): void
	{
		try {
			doFoo();
			$a = 1;
			(static function (): void {
				throw new \InvalidArgumentException();
			})();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doThrowInClosureVariablePassedAsCallable(): void
	{
		$callback = static function (int $i): int {
			throw new \InvalidArgumentException();
		};
		try {
			doFoo();
			$a = 1;
			array_map($callback, [1, 2]);
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doAnnotatedThrowStatement(): void
	{
		try {
			doFoo();
			$a = 1;
			/** @throws \InvalidArgumentException */
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doDocumentedThrowInClosureArg(): void
	{
		try {
			doFoo();
			$a = 1;
			array_map(function (int $i): int {
				$this->throwInvalidArgument();

				return $i;
			}, [1, 2]);
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function throwInvalidArgument(): void
	{

	}

}
