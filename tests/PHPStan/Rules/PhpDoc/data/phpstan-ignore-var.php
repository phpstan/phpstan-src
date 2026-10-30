<?php

namespace PhpStanIgnoreVarRules;

class Foo
{

	/** @return iterable<array-key, \DateTime> */
	public function getTimes(): iterable
	{
		return [];
	}

	public function doFoo(int $i): void
	{
		/**
		 * @var \DateTime[] $times
		 * @phpstan-ignore-var
		 */
		$times = $this->getTimes();

		/**
		 * @var string $s
		 * @phpstan-ignore-var
		 */
		$s = $i;

		/**
		 * @var int $wrongName
		 * @phpstan-ignore-var
		 */
		$j = $i;

		/**
		 * @var string $item
		 * @phpstan-ignore-var
		 */
		foreach ([$i] as $item) {
		}
	}

	public function doBar(int $i): void
	{
		/**
		 * @var string $i
		 * @phpstan-ignore-var
		 */
		echo $i;
	}

	public function doBaz(int $i): int
	{
		/**
		 * @var string
		 * @phpstan-ignore-var
		 */
		return $i;
	}

}

class WithoutVarTag
{

	/**
	 * @phpstan-ignore-var
	 */
	public function doFoo(int $i): void
	{
		/**
		 * @phpstan-ignore-var
		 */
		$j = $i;

		/**
		 * @phpstan-ignore-var
		 */
		$this->doBar();

		/**
		 * @phpstan-var int $k
		 * @phpstan-ignore-var
		 */
		$k = $i;

		/**
		 * @phpstan-ignore-var
		 */
		foreach ([$i] as $item) {
		}
	}

	public function doBar(): int
	{
		/**
		 * @phpstan-ignore-var
		 */
		return 1;
	}

	public function doBaz(object $o): void
	{
		/**
		 * @var array<int $o
		 * @phpstan-ignore-var
		 */
		$o->doFoo();
	}

}

class ReportedOnce
{

	public function doFoo(int $other): void
	{
		/**
		 * @var string $a
		 * @phpstan-ignore-var
		 */
		[$a, $b] = [1, 2];

		/**
		 * @var string $other
		 * @phpstan-ignore-var
		 */
		$x = 1;
	}

	public function doBar(int $i): int
	{
		/**
		 * @var string $i
		 * @phpstan-ignore-var
		 */
		return $i;
	}

}
