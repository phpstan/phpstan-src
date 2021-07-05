<?php

namespace ComparisonOperators;

use function PHPStan\Testing\assertType;

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

class ComparisonOperatorsInTypeSpecifier
{

	public function null(?int $i, ?float $f, ?string $s, ?bool $b): void
	{
		if ($i > null) {
			assertType('int<min, -1>|int<1, max>', $i);
		}
		if ($i >= null) {
			assertType('int|null', $i);
		}
		if ($i < null) {
			assertType('*NEVER*', $i);
		}
		if ($i <= null) {
			assertType('0|null', $i);
		}

		if ($f > null) {
			assertType('float', $f);
		}
		if ($f >= null) {
			assertType('float|null', $f);
		}
		if ($f < null) {
			assertType('*NEVER*', $f);
		}
		if ($f <= null) {
			assertType('0.0|null', $f);
		}

		if ($s > null) {
			assertType('non-empty-string', $s);
		}
		if ($s >= null) {
			assertType('string|null', $s);
		}
		if ($s < null) {
			assertType('*NEVER*', $s);
		}
		if ($s <= null) {
			assertType('\'\'|null', $s);
		}

		if ($b > null) {
			assertType('true', $b);
		}
		if ($b >= null) {
			assertType('bool|null', $b);
		}
		if ($b < null) {
			assertType('*NEVER*', $b);
		}
		if ($b <= null) {
			assertType('false|null', $b);
		}
	}

	public function bool(?bool $b): void
	{
		if ($b > false) {
			assertType('true', $b);
		}
		if ($b >= false) {
			assertType('bool|null', $b);
		}
		if ($b < false) {
			assertType('*NEVER*', $b);
		}
		if ($b <= false) {
			assertType('false|null', $b);
		}

		if ($b > true) {
			assertType('*NEVER*', $b);
		}
		if ($b >= true) {
			assertType('true', $b);
		}
		if ($b < true) {
			assertType('false|null', $b);
		}
		if ($b <= true) {
			assertType('bool|null', $b);
		}
	}

	public function string(?string $s): void
	{
		if ($s < '') {
			assertType('*NEVER*', $s);
		}
		if ($s <= '') {
			assertType('string|null', $s); // Would be nice to have ''|null
		}
	}

	public function intPositive10(?int $i, ?float $f): void
	{
		if ($i > 10) {
			assertType('int<11, max>', $i);
		}
		if ($i >= 10) {
			assertType('int<10, max>', $i);
		}
		if ($i < 10) {
			assertType('int<min, 9>|null', $i);
		}
		if ($i <= 10) {
			assertType('int<min, 10>|null', $i);
		}

		if ($f > 10) {
			assertType('float', $f);
		}
		if ($f >= 10) {
			assertType('float', $f);
		}
		if ($f < 10) {
			assertType('float|null', $f);
		}
		if ($f <= 10) {
			assertType('float|null', $f);
		}
	}

	public function intNegative10(?int $i, ?float $f): void
	{
		if ($i > -10) {
			assertType('int<-9, max>', $i);
		}
		if ($i >= -10) {
			assertType('int<-10, max>', $i);
		}
		if ($i < -10) {
			assertType('int<min, -11>|null', $i);
		}
		if ($i <= -10) {
			assertType('int<min, -10>|null', $i);
		}

		if ($f > -10) {
			assertType('float', $f);
		}
		if ($f >= -10) {
			assertType('float', $f);
		}
		if ($f < -10) {
			assertType('float|null', $f);
		}
		if ($f <= -10) {
			assertType('float|null', $f);
		}
	}

	public function intZero(?int $i, ?float $f): void
	{
		if ($i > 0) {
			assertType('int<1, max>', $i);
		}
		if ($i >= 0) {
			assertType('int<0, max>|null', $i);
		}
		if ($i < 0) {
			assertType('int<min, -1>', $i);
		}
		if ($i <= 0) {
			assertType('int<min, 0>|null', $i);
		}

		if ($f > 0) {
			assertType('float', $f);
		}
		if ($f >= 0) {
			assertType('float|null', $f);
		}
		if ($f < 0) {
			assertType('float', $f);
		}
		if ($f <= 0) {
			assertType('float|null', $f);
		}
	}

	public function float10(?int $i): void
	{
		if ($i > 10.0) {
			assertType('int<11, max>', $i);
		}
		if ($i >= 10.0) {
			assertType('int<10, max>', $i);
		}
		if ($i < 10.0) {
			assertType('int<min, 9>|null', $i);
		}
		if ($i <= 10.0) {
			assertType('int<min, 10>|null', $i);
		}

		if ($i > 10.1) {
			assertType('int<11, max>', $i);
		}
		if ($i >= 10.1) {
			assertType('int<11, max>', $i);
		}
		if ($i < 10.1) {
			assertType('int<min, 10>|null', $i);
		}
		if ($i <= 10.1) {
			assertType('int<min, 10>|null', $i);
		}
	}

	public function floatZero(?int $i): void
	{
		if ($i > 0.0) {
			assertType('int<1, max>', $i);
		}
		if ($i >= 0.0) {
			assertType('int<0, max>|null', $i);
		}
		if ($i < 0.0) {
			assertType('int<min, -1>', $i);
		}
		if ($i <= 0.0) {
			assertType('int<min, 0>|null', $i);
		}
	}

	public function ranges(int $a, ?int $b): void
	{
		if ($a >= 17 && $a <= 42) {
			if ($b < $a) {
				assertType('int<min, 41>|null', $b);
			}
			if ($b <= $a) {
				assertType('int<min, 42>|null', $b);
			}
			if ($b > $a) {
				assertType('int<18, max>', $b);
			}
			if ($b >= $a) {
				assertType('int<17, max>', $b);
			}
		}

		if ($a >= -17 && $a <= 42) {
			if ($b < $a) {
				assertType('int<min, 41>|null', $b);
			}
			if ($b <= $a) {
				assertType('int<min, 42>|null', $b);
			}
			if ($b > $a) {
				assertType('int<-16, max>', $b);
			}
			if ($b >= $a) {
				assertType('int<-17, max>|null', $b);
			}
		}
	}

}
