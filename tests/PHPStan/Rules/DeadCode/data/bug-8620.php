<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug8620;

class HelloWorld
{
	public function nullCoalesceAndConcatenation (?int $a = null): int
	{
		$key = ($a ?? "x") . "-";
		if ($key === "x-") { return 0; }

		return 1;
	}

	public function nullCoalesce (?int $a = null): int
	{
		$key = ($a ?? "");
		if ($key === "") { return 0; }

		return 1;
	}

	public function nullCheck (?int $a = null): int
	{
		if (is_null ($a)) { return 0; }

		return 1;
	}
}
