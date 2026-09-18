<?php // lint >= 8.0

namespace FunctionCallStatementNoSideEffectsThrowExpr;

class Foo
{

	/**
	 * @param list<int> $a
	 */
	public function doFoo(array $a): void
	{
		array_map(static fn (int $i) => throw new \ValueError('x'), $a);
		array_map(static function (int $i): int {
			throw new \ValueError('x');
		}, $a);
		$callback = static function (int $i): int {
			throw new \ValueError('x');
		};
		array_map($callback, $a);
		array_map(static fn (int $i) => $i * 2, $a);
	}

}
