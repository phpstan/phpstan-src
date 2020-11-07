<?php

namespace MatchExpr;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param 1|2|3|4 $i
	 */
	public function doFoo(int $i): void
	{
		assertType('*NEVER*', match ($i) {
			0 => $i,
		});
		assertType('1', match ($i) {
			1 => $i,
		});
		assertType('1|2', match ($i) {
			1, 2 => $i,
		});
		assertType('1|2|3', match ($i) {
			1, 2, 3 => $i,
		});
	}

}
