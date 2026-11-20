<?php declare(strict_types = 1);

namespace FloatRangeNanUnion;

use function PHPStan\Testing\assertType;

class Foo
{

	public function afterNoOpGuard(float $f): void
	{
		if (is_finite($f) && $f !== 0.0) {
			assertType('float<-1.7976931348623157E+308, -5.0E-324>|float<5.0E-324, 1.7976931348623157E+308>', $f);
		} else {
			assertType('-INF|0.0|INF|NAN', $f);
		}
		assertType('float', $f);
	}

	public function signChain(float $f): void
	{
		if (is_nan($f)) {
			assertType('NAN', $f);
		} elseif ($f > 0.0) {
			assertType('float<5.0E-324, max>', $f);
		} elseif ($f < 0.0) {
			assertType('float<min, -5.0E-324>', $f);
		} else {
			assertType('0.0', $f);
		}
		assertType('float', $f);
	}

	public function nanOrZero(float $f): void
	{
		if (is_nan($f) || $f === 0.0) {
			assertType('0.0|NAN', $f);
		}
		assertType('float', $f);
	}

	public function nullable(?float $f): void
	{
		if ($f !== null && is_finite($f) && $f !== 0.0) {
			assertType('float<-1.7976931348623157E+308, -5.0E-324>|float<5.0E-324, 1.7976931348623157E+308>', $f);
		}
		assertType('float|null', $f);
	}

	/**
	 * @param float<min, -5.0E-324>|float<5.0E-324, max>|0.0|NAN $f
	 */
	public function phpDoc(float $f): void
	{
		assertType('float', $f);
	}

}
