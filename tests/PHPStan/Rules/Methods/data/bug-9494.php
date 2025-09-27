<?php

namespace Bug9494;

class Fib
{
	/** @var array<int, ?int> 0-indexed memoization */
	protected $mem = [];

	public function __construct(public int $limit) {
		$this->mem = array_fill(2, $limit, null);
	}

	/**
	 * Calculate fib, 1-indexed
	 */
	public function fib(int $n): int
	{
		if ($n < 1 || $n > $this->limit) {
			throw new \RangeException();
		}

		if ($n == 1 || $n == 2) {
			return 1;
		}

		if (is_null($this->mem[$n - 1])) {
			$this->mem[$n - 1] = $this->fib($n - 1) + $this->fib($n - 2);
		}

		return $this->mem[$n - 1];  // Is always an int at this stage
	}

	/**
	 * Calculate fib, 0-indexed
	 */
	public function fib0(int $n0): int
	{
		if ($n0 < 0 || $n0 >= $this->limit) {
			throw new \RangeException();
		}

		if ($n0 == 0 || $n0 == 1) {
			return 1;
		}

		if (is_null($this->mem[$n0])) {
			$this->mem[$n0] = $this->fib0($n0 - 1) + $this->fib0($n0 - 2);
		}

		return $this->mem[$n0];
	}
}
