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
