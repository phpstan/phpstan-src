<?php declare(strict_types = 1);

namespace Bug12462;

class A {
	function methodReturningYieldingClosure (): int
	{
		return function () { yield ''; };
	}

	function methodReturningYieldingArrowFunction (): int
	{
		return fn () => yield '';
	}

	function methodRetuningYieldingAnonymousClass (): int
	{
		return new class () {
			public function f(): \Generator {
				yield '';
			}
		};
	}
}