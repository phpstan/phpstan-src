<?php declare(strict_types = 1);

namespace Bug15713;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param float<0.0, 1.0> $ratio
	 * @param non-negative-float $total
	 */
	public function budget(int $used, float $ratio, float $total): string
	{
		$limit = $ratio * $total;
		assertType('NAN|float<0.0, max>', $limit);
		if ($used < $limit) {
			return 'ok';
		}
		assertType('int', $used);
		if ($used < 0) {
			return 'refund';
		}

		return 'over';
	}

	/** @param float<-1.0, 1.0> $x */
	public function root(float $x, int $n): void
	{
		$root = sqrt($x);
		assertType('NAN|float<0.0, 1.0>', $root);
		if ($root <= $n) {
			return;
		}
		assertType('int', $n);
	}

	/** @param -1|-2 $n */
	public function literalNan(int $n, int $i): void
	{
		if (NAN >= $n) {
			return;
		}
		assertType('-2|-1', $n);

		if ($i < NAN) {
			return;
		}
		assertType('int', $i);
	}

	public function bothFinite(int $i, float $f): void
	{
		if (is_nan($f)) {
			return;
		}
		if ($i < $f) {
			return;
		}
		assertType('int', $i);
	}

	/** @param int<-5, -1> $r */
	public function minMax(int $r, int $i): void
	{
		assertType('NAN|int<-5, -1>', max(NAN, $r));
		assertType('NAN|int<-5, -1>', max($r, NAN));
		assertType('NAN|int', max(NAN, $i));
		assertType('NAN|int<-5, -1>', min(NAN, $r));
	}

	/**
	 * @param array<int> $a
	 * @param float<-1.0, 1.0> $x
	 */
	public function countAgainstNan(array $a, string $s, float $x): void
	{
		$root = sqrt($x);
		if (count($a) < $root) {
			return;
		}
		assertType('NAN|float<0.0, 1.0>', $root);

		if (strlen($s) <= $root) {
			return;
		}
		assertType('NAN|float<0.0, 1.0>', $root);
	}

	public function finiteRanges(float $f, float $g): void
	{
		if (is_nan($f) || is_nan($g)) {
			return;
		}
		if ($f < $g) {
			return;
		}
		assertType('float<min, max>', $f);
		assertType('float<min, max>', $g);
	}

	/** @param float<-1.0, 1.0> $x */
	public function notFalseContext(array $a, string $s, float $x): void
	{
		$root = sqrt($x) + 2.0;
		assertType('NAN|float<2.0, 3.0>', $root);
		// the comparison being anything but false is it being true, which no NAN is
		if ((count($a) < $root) !== false) {
			assertType('float<2.0, 3.0>', $root);
		}
		if ((strlen($s) < $root) !== false) {
			assertType('float<2.0, 3.0>', $root);
		}
	}

}
