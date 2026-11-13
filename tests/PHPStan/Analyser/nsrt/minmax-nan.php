<?php declare(strict_types = 1);

namespace MinMaxNan;

use function PHPStan\Testing\assertType;

class Foo
{

	public function floats(float $f, float $g): void
	{
		assertType('NAN|float<0.0, max>', max(0.0, $f));
		assertType('NAN|float<0.0, max>', max($f, 0.0));
		assertType('NAN|float<min, 1.0>', min(1.0, $f));
		assertType('NAN|float<min, 1.0>', min($f, 1.0));
		assertType('float', max($f, $g));
		assertType('float', min($f, $g));

		assertType('NAN|float<0.0, 1.0>', max(0.0, min(1.0, $f)));
		assertType('NAN|float<0.0, 1.0>', min(1.0, max(0.0, $f)));
		assertType('NAN|float<0.0, 1.0>', max(min($f, 1.0), 0.0));
	}

	/**
	 * @param float<0.0, 1.0> $unit
	 * @param float<10.0, 20.0> $high
	 */
	public function ranges(float $unit, float $high, float $f): void
	{
		assertType('float<10.0, 20.0>', max($unit, $high));
		assertType('float<10.0, 20.0>', max($high, $unit));
		assertType('float<0.0, 1.0>', min($unit, $high));
		assertType('float<0.0, 1.0>', min($high, $unit));
		assertType('float<0.0, 1.0>', max(0.0, min(1.0, $unit)));

		assertType('NAN|float<0.0, max>', max($unit, $f));
		assertType('NAN|float<0.0, max>', max($f, $unit));
		assertType('NAN|float<min, 20.0>', min($high, $f));
		assertType('NAN|float<min, 20.0>', min($f, $high));
	}

	public function nan(bool $b): void
	{
		assertType('1|NAN', max(1, NAN));
		assertType('1|NAN', max(NAN, 1));
		assertType('1|NAN', min(1, NAN));
		assertType('1|NAN', min(NAN, 1));
		assertType('NAN', max(NAN, NAN));

		$n = $b ? NAN : 2.0;
		assertType('1.0|2.0|NAN', max($n, 1.0));
		assertType('1.0|2.0|NAN', max(1.0, $n));
		assertType('2.0|3.0|NAN', min($n, 3.0));
		assertType('2.0|3.0|NAN', min(3.0, $n));
	}

	public function mixedNumbers(int $i, float $f, string $s): void
	{
		assertType('0|NAN|float<5.0E-324, max>', max(0, $f));
		assertType('0|NAN|float<0.0, max>', max($f, 0));
		assertType('float|int', max($i, $f));
		assertType('float|int', min($f, $i));
		assertType('float|string', max($f, $s));
		assertType("NAN|'1'", max('1', NAN));
		assertType("NAN|'1'", min(NAN, '1'));
	}

	/** @param int<0, max> $count */
	public function ties(int $count): void
	{
		assertType('0', max(0, 0.0));
		assertType('0.0', max(0.0, 0));
		assertType('0|0.0', min(0, 0.0));
		assertType('0|0.0', min(0.0, 0));
		assertType('0.0', max(0.0, -0.0));
		assertType('-0.0|0.0', min(0.0, -0.0));
		assertType("1|'1'", min(1, '1'));
		assertType('1', max(1, '1'));
		assertType('0|0.0', min($count, 0.0));
		assertType('0|0.0', min(0.0, $count));
	}

	/**
	 * @param int<1, 10> $i
	 * @param int<5, 20> $j
	 */
	public function ints(int $i, int $j, int $k): void
	{
		assertType('int<5, 20>', max($i, $j));
		assertType('int<1, 10>', min($i, $j));
		assertType('int<1, max>', max($i, $k));
		assertType('int<min, 10>', min($i, $k));
		assertType('2', max(1, 2));
		assertType('1', min(1, 2));
	}

	public function intOrFloat(int|float $n, int $i): void
	{
		assertType('NAN|float<min, 100.0>|int<min, 100>', min($n, 100));
		assertType('NAN|float<min, 100.0>|int<min, 100>', min(100, $n));
		assertType('NAN|float<100.0, max>|int<100, max>', max($n, 100));
		assertType('NAN|float<100.00000000000001, max>|int<100, max>', max(100, $n));
		assertType('float|int', min($n, $i));
		assertType('float|int', min($i, $n));
	}

	public function callables(float $f): void
	{
		assertType('NAN|float<0.0, max>', call_user_func('max', 0.0, $f));
		assertType('NAN|float<0.0, max>', call_user_func('max', $f, 0.0));
		assertType('NAN|float<min, 1.0>', call_user_func('min', 1.0, $f));
		assertType('NAN|float<min, 1.0>', call_user_func('min', $f, 1.0));
		assertType('non-empty-list<NAN|float<0.0, max>>', array_map('max', [0.0], [$f]));
		assertType('non-empty-list<NAN|float<0.0, max>>', array_map('max', [$f], [0.0]));
	}

}
