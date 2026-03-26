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
