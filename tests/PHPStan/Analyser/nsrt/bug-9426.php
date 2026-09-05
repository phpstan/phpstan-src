<?php

namespace Bug9426;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

final class A
{

	/**
	 * @param array{something?: string} $a
	 */
	public function something(array $a): void
	{
		if (isset($a['something'])) {
			$b = new \DateTimeImmutable();
		}

		if (!isset($a['something'])) {
			assertType('array{}', $a);
			assertVariableCertainty(TrinaryLogic::createNo(), $b);
		} else {
			assertType('array{something: string}', $a);
			assertVariableCertainty(TrinaryLogic::createYes(), $b);
		}
	}

	/**
	 * @param array{something?: string|null} $a
	 */
	public function nullableValueStaysUntouched(array $a): void
	{
		if (!isset($a['something'])) {
			assertType('array{something?: string|null}', $a);
		} else {
			assertType('array{something: string}', $a);
		}
	}

}
