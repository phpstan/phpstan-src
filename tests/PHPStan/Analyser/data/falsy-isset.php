<?php

namespace FalsyIsset;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

class HelloWorld
{
	public function undefinedVar(): void
	{
		if (isset($a['bar'])) {
			assertType("*ERROR*", $a);
		} else {
			assertType("*ERROR*", $a);
		}
	}

	public function definedVar($a): void
	{
		if (isset($a['bar'])) {
			assertType("mixed~null", $a);
		} else {
			assertType("mixed", $a);
		}
	}

	/**
	 * @param array{bar?: null}|array{bar?: 'hello'} $a
	 */
	public function optionalOffsetNull($a): void
	{
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType('array{bar?: null}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}|array{bar?: null}", $a);
	}

	/**
	 * @param array{bar?: 'world'}|array{bar?: 'hello'} $a
	 */
	public function optionalOffsetNonNull($a): void
	{
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType('array{}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{}|array{bar: 1}", $a);
	}

	public function maybeCertainNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => null];
			if (rand() % 3) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: null}|array{bar: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
			assertType('array{bar?: null}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertType("array{bar?: null}|array{bar: 1}", $a);
	}

	public function maybeCertainNonNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => 'world'];
			if (rand() % 3) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $a);
			assertType('*ERROR*', $a);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertType("array{bar: 1}", $a);
	}

	public function yesCertainNull(): void
	{
		$a = ['bar' => null];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: 'hello'}|array{bar: null}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: null}", $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}|array{bar: null}", $a);
	}

	public function yesCertainNonNull(): void
	{
		$a = ['bar' => 'world'];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType('*NEVER*', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}", $a);
	}
}
