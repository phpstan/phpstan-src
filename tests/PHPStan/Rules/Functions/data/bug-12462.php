<?php declare(strict_types = 1);

namespace Bug12462;

function functionReturningYieldingClosure (): int
{
	return function () { yield ''; };
}

function functionReturningYieldingArrowFunction (): int
{
	return fn () => yield '';
}

function functionRetuningYieldingAnonymousClass (): int
{
	return new class () {
		public function f(): \Generator {
			yield '';
		}
	};
}