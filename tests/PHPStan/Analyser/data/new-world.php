<?php declare(strict_types = 1);

namespace NewWorldTypeInference;

use function PHPStan\Testing\assertType;

class Foo
{

	public function scalarsAndAssigns(): void
	{
		$a = 1;
		assertType('1', $a);

		$b = 'foo';
		assertType('\'foo\'', $b);

		$c = 1.5;
		assertType('1.5', $c);

		$d = $e = 5;
		assertType('5', $d);
		assertType('5', $e);
	}

	public function functionCalls(int $i, string $s): void
	{
		assertType('int', $i);
		assertType('string', $s);

		$len = strlen($s);
		assertType('int<0, max>', $len);

		$cnt = strlen('abc');
		assertType('3', $cnt);

		$abs = abs($i);
		assertType('int<0, max>', $abs);

		$abs2 = abs(7);
		assertType('7', $abs2);

		$nested = strlen(strtoupper($s));
		assertType('int<0, max>', $nested);

		$pi = pi();
		assertType('float', $pi);
	}

	public function narrowingInIf(string $s): void
	{
		$v = 1;
		if ($v) {
			assertType('1', $v);
		} else {
			assertType('*NEVER*', $v);
		}

		$w = rand(0, 1);
		assertType('int<0, 1>', $w);
		if ($w) {
			assertType('1', $w);
		} else {
			assertType('0', $w);
		}

		$len = strlen($s);
		assertType('int<0, max>', $len);
		if ($len) {
			assertType('int<1, max>', $len);
		} else {
			assertType('0', $len);
		}
	}

	public function assignInCondition(string $s): void
	{
		if ($len = strlen($s)) {
			assertType('int<1, max>', $len);
		} else {
			assertType('0', $len);
		}
	}

	public function functionAsserts(): void
	{
		$m = mixedValue();
		assertType('mixed', $m);
		assertInt($m);
		assertType('int', $m);
	}

	public function conditionalReturnType(int $i): void
	{
		assertType('bool', isPositive($i));
		if (isPositive($i)) {
			assertType('int<1, max>', $i);
		} else {
			assertType('int<min, 0>', $i);
		}
	}

	public function conditionalExpressionHolders(string $s): void
	{
		$len = strlen($s);
		if ($len) {
			assertType('non-empty-string', $s);
			assertType('int<1, max>', $len);
		} else {
			assertType('\'\'', $s);
			assertType('0', $len);
		}
	}

	public function assignByReference(): void
	{
		$q = 1;
		$r = &$q;
		assertType('1', $r);
	}

}

function mixedValue(): mixed
{
	return 1;
}

/**
 * @phpstan-assert int $value
 */
function assertInt(mixed $value): void
{
}

/**
 * @return ($i is int<1, max> ? true : false)
 */
function isPositive(int $i): bool
{
	return $i >= 1;
}
