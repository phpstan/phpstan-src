<?php

namespace CallablesWithoutCheckNullables;

use Closure;

class Foo
{

	public function doFoo(): void
	{
		$this->doBar(function (?float $f): ?float {
			return $f;
		});
		$this->doBaz(function (?float $f): ?float {
			return $f;
		});
		$this->doBar(function (?float $f): float {
			return $f;
		});
		$this->doBaz(function (?float $f): float {
			return $f;
		});

		$this->doBar(function (float $f): float {
			return $f;
		});
		$this->doBaz(function (float $f): float {
			return $f;
		});

		$this->doBar2(function (?float $f): ?float {
			return $f;
		});
		$this->doBaz2(function (?float $f): ?float {
			return $f;
		});
		$this->doBar2(function (?float $f): float {
			return $f;
		});
		$this->doBaz2(function (?float $f): float {
			return $f;
		});

		$this->doBar2(function (float $f): float {
			return $f;
		});
		$this->doBaz2(function (float $f): float {
			return $f;
		});
	}

	/**
	 * @param callable(float|null): (float|null) $cb
	 * @return void
	 */
	public function doBar(callable $cb): void
	{

	}

	/**
	 * @param Closure(float|null): (float|null) $cb
	 * @return void
	 */
	public function doBaz(Closure $cb): void
	{

	}

	/**
	 * @param callable(float|null): float $cb
	 * @return void
	 */
	public function doBar2(callable $cb): void
	{

	}

	/**
	 * @param Closure(float|null): float $cb
	 * @return void
	 */
	public function doBaz2(Closure $cb): void
	{

	}

}
