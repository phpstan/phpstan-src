<?php

namespace ComparisonOperators;

use function PHPStan\Analyser\assertType;

class ComparisonOperators
{
	public function null(): void
	{
		assertType('false', -1 < null);
		assertType('false', 0 < null);
		assertType('false', 1 < null);
		assertType('false', true < null);
		assertType('false', false < null);
		assertType('false', '1' < null);
		assertType('true', 0 <= null);
		assertType('false', '0' <= null);

		assertType('true', null < -1);
		assertType('false', null < 0);
		assertType('true', null < 1);
		assertType('true', null < true);
		assertType('false', null < false);
		assertType('true', null < '1');
		assertType('true', null <= '0');
	}

	public function bool(): void
	{
		assertType('true', true > false);
		assertType('true', true >= false);
		assertType('false', true < false);
		assertType('false', true <= false);
		assertType('false', false > true);
		assertType('false', false >= true);
		assertType('true', false < true);
		assertType('true', false <= true);
	}

	public function string(): void
	{
		assertType('false', 'foo' < 'bar');
		assertType('false', 'foo' <= 'bar');
		assertType('true', 'foo' > 'bar');
		assertType('true', 'foo' >= 'bar');
	}

	public function float(): void
	{
		assertType('true', 1.9 > 1);
		assertType('true', '1.9' > 1);

		assertType('false', 1.9 > 2.1);
		assertType('true', 1.9 > 1.5);
		assertType('true', 1.9 < 2.1);
		assertType('false', 1.9 < 1.5);
	}

	public function unions(int $a, int $b): void
	{
		if (($a === 17 || $a === 42) && ($b === 3 || $b === 7)) {
			assertType('false', $a < $b);
			assertType('true', $a > $b);
			assertType('false', $a <= $b);
			assertType('true', $a >= $b);
		}
		if (($a === 11 || $a === 42) && ($b === 3 || $b === 11)) {
			assertType('false', $a < $b);
			assertType('bool', $a > $b);
			assertType('bool', $a <= $b);
			assertType('true', $a >= $b);
		}
	}

	public function ranges(int $a, int $b): void
	{
		if ($a >= 10 && $a <= 20) {
			if ($b >= 30 && $b <= 40) {
				assertType('true', $a < $b);
				assertType('false', $a > $b);
				assertType('true', $a <= $b);
				assertType('false', $a >= $b);
			}
		}
		if ($a >= 10 && $a <= 25) {
			if ($b >= 25 && $b <= 40) {
				assertType('bool', $a < $b);
				assertType('false', $a > $b);
				assertType('true', $a <= $b);
				assertType('bool', $a >= $b);
			}
		}
	}
}
