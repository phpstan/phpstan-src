<?php

namespace FalsyIsset;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

class HelloWorld
{
	public function maybeCertainNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => null];
			if (rand() % 2) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: null}|array{bar: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertType('array{}', $a);
		}

		assertType("array{bar: null}|array{bar: 1}", $a);
	}

	public function maybeCertainNonNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => 'world'];
			if (rand() % 2) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: 'world'}|array{bar: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertType("array{bar: 'world'}|array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertType('array{}', $a);
		}

		assertType("array{bar: 1}", $a);
	}

	public function yesCertainNull(): void
	{
		$a = ['bar' => null];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: null}|array{bar: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertType('array{}', $a);
		}

		assertType("array{bar: null}|array{bar: 1}", $a);
	}

	public function yesCertainNonNull(): void
	{
		$a = ['bar' => 'world'];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: 'world'}|array{bar: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertType("array{bar: 'world'}|array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertType('array{}', $a);
		}

		assertType("array{bar: 1}", $a);
	}
}
