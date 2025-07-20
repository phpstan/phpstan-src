<?php // lint >= 8.4

declare(strict_types = 1);

namespace BcMathNumber;

use BcMath\Number;
use function PHPStan\Testing\assertType;

class Foo
{
	public function bcVsBc(Number $a, Number $b): void
	{
		assertType('BcMath\Number', $a + $b);
		assertType('BcMath\Number', $a - $b);
		assertType('BcMath\Number', $a * $b);
		assertType('BcMath\Number', $a / $b);
		assertType('BcMath\Number', $a % $b);
		assertType('non-falsy-string', $a . $b);
		assertType('BcMath\Number', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsInt(Number $a, int $b): void
	{
		assertType('BcMath\Number', $a + $b);
		assertType('BcMath\Number', $a - $b);
		assertType('BcMath\Number', $a * $b);
		assertType('BcMath\Number', $a / $b);
		assertType('BcMath\Number', $a % $b);
		assertType('non-falsy-string', $a . $b);
		assertType('BcMath\Number', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsFloat(Number $a, float $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-falsy-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	/** @param numeric-string $b */
	public function bcVsNumericString(Number $a, string $b): void
	{
		assertType('BcMath\Number', $a + $b);
		assertType('BcMath\Number', $a - $b);
		assertType('BcMath\Number', $a * $b);
		assertType('BcMath\Number', $a / $b);
		assertType('BcMath\Number', $a % $b);
		assertType('non-falsy-string', $a . $b);
		assertType('BcMath\Number', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsNonNumericString(Number $a, string $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-empty-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsBool(Number $a, bool $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-empty-string&numeric-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsNull(Number $a): void
	{
		$b = null;
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('0', $a * $b); // BUG: This throws type error, but getMulType assumes that since null (mostly) behaves like zero, it will be zero.
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-empty-string&numeric-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('false', $a < $b);
		assertType('false', $a <= $b);
		assertType('true', $a > $b);
		assertType('true', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('false', $a && $b);
		assertType('bool', $a || $b);
		assertType('false', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsArray(Number $a, array $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('*ERROR*', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsObject(Number $a, object $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('*ERROR*', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('true', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('true', $a or $b);
	}

	/** @param resource $b */
	public function bcVsResource(Number $a, $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-empty-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('true', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('true', $a or $b);
	}

	public function bcVsCallable(Number $a, callable $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('*ERROR*', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('true', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('true', $a or $b);
	}

	public function bcVsIterable(Number $a, iterable $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('*ERROR*', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('bool', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('bool', $a or $b);
	}

	public function bcVsStringable(Number $a, \Stringable $b): void
	{
		assertType('*ERROR*', $a + $b);
		assertType('*ERROR*', $a - $b);
		assertType('*ERROR*', $a * $b);
		assertType('*ERROR*', $a / $b);
		assertType('*ERROR*', $a % $b);
		assertType('non-empty-string', $a . $b);
		assertType('*ERROR*', $a ** $b);
		assertType('*ERROR*', $a << $b);
		assertType('*ERROR*', $a >> $b);
		assertType('bool', $a < $b);
		assertType('bool', $a <= $b);
		assertType('bool', $a > $b);
		assertType('bool', $a >= $b);
		assertType('int<-1, 1>', $a <=> $b);
		assertType('bool', $a == $b);
		assertType('bool', $a != $b);
		assertType('*ERROR*', $a & $b);
		assertType('*ERROR*', $a ^ $b);
		assertType('*ERROR*', $a | $b);
		assertType('bool', $a && $b);
		assertType('true', $a || $b);
		assertType('bool', $a and $b);
		assertType('bool', $a xor $b);
		assertType('true', $a or $b);
	}

	public function bcVsNever(Number $a): void
	{
		for ($b = 1; $b < count([]); $b++) {
			assertType('*NEVER*', $a + $b);
			assertType('*ERROR*', $a - $b); // Inconsistency: getPlusType handles never types right at the beginning, getMinusType doesn't.
			assertType('*ERROR*', $a * $b);
			assertType('*ERROR*', $a / $b);
			assertType('*NEVER*', $a % $b);
			assertType('non-empty-string&numeric-string', $a . $b);
			assertType('*ERROR*', $a ** $b);
			assertType('*NEVER*', $a << $b);
			assertType('*NEVER*', $a >> $b);
			assertType('bool', $a < $b);
			assertType('bool', $a <= $b);
			assertType('bool', $a > $b);
			assertType('bool', $a >= $b);
			assertType('*NEVER*', $a <=> $b);
			assertType('bool', $a == $b);
			assertType('bool', $a != $b);
			assertType('*NEVER*', $a & $b);
			assertType('*NEVER*', $a ^ $b);
			assertType('*NEVER*', $a | $b);
			assertType('bool', $a && $b);
			assertType('bool', $a || $b);
			assertType('bool', $a and $b);
			assertType('bool', $a xor $b);
			assertType('bool', $a or $b);
		}
	}
}
