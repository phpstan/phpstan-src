<?php // lint >= 8.0

/*
 * instructions: 51.80M
 * cycles: 73.74M
 * memory: 310.2KiB
 */
declare(strict_types=1);

namespace ConditionalExpressionInfiniteLoop;
class test
{
	public function test2(bool $isFoo, bool $isBar): void
	{
		$a = match (true) {
			$isFoo && $isBar => $foo = 1,
			$isFoo || $isBar => $foo = 2,
			default => $foo = null,
		};
	}
}
