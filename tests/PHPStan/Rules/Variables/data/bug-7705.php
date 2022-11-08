<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7705;

class test
{
	public function test1(bool $isFoo): void
	{
		match (true) {
			$isFoo => $foo = 1,
			default => $foo = null,
		};

		if (!is_null($foo)) {
			$bar = true;
		}

	}

	public function test2(bool $isFoo, bool $isBar): void
	{

		match (true) {
			$isFoo && $isBar => $foo = 1,
			$isFoo || $isBar => $foo = 2,
			default => $foo = null,
		};

		if (!is_null($foo)) {
			$bar = true;
		}

	}
}
