<?php

namespace ReportUnsafeArrayStringKeyCastingAccepts;

use stdClass;

class Foo
{

	/** @param array<string, stdClass> $a */
	public function doFoo(array $a): void
	{

	}

	/** @param array<int|string, stdClass> $a */
	public function doBar(array $a): void
	{

	}

	/** @param array<non-decimal-int-string, stdClass> $a */
	public function doBaz(array $a): void
	{

	}

	public function doTest(string $s): void
	{
		$a = [$s => new stdClass()];
		$this->doFoo($a);
		$this->doBar($a);
		$this->doBaz($a);

		$b = [];
		$b[$s] = new stdClass();
		$this->doFoo($b);
		$this->doBar($b);
		$this->doBaz($b);
	}

}

class UnsealedArrayShape
{

	/**
	 * @param array{stdClass, ...<string, stdClass>} $a
	 * @return void
	 */
	public function doFoo(array $a): void
	{

	}

	/**
	 * @param array{stdClass, ...<int|string, stdClass>} $a
	 * @return void
	 */
	public function doBar(array $a): void
	{

	}

	/**
	 * @param array{stdClass, ...<non-decimal-int-string, stdClass>} $a
	 * @return void
	 */
	public function doBaz(array $a): void
	{

	}

	public function doTest(string $s): void
	{
		$a = [new stdClass(), $s => new stdClass()];
		$this->doFoo($a);
		$this->doBar($a);
		$this->doBaz($a);

		$b = [new stdClass()];
		$b[$s] = new stdClass();
		$this->doFoo($b);
		$this->doBar($b);
		$this->doBaz($b);
	}

}
