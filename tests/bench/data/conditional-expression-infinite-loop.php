<?php // lint >= 8.0

/*
 * instructions: 53.47M
 * cycles: 75.86M
 * memory: 36.05MiB
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
