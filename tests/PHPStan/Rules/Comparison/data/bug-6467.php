<?php declare(strict_types = 1);

namespace Bug6467;

class Comparison
{
	public function compare(): void
	{
		$values = $sortedValues = [0.5, 1, 2, 0.8, 0.4];
		sort($sortedValues);
		$expected = $sortedValues[2] + 0.05;
		foreach (array_fill(0, 5, null) as $index => $null) {
			$success = $values[$index] < $expected;
		}
	}
}
