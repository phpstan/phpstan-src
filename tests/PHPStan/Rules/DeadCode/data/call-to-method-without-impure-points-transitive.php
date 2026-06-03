<?php

namespace CallToMethodWithoutImpurePointsTransitive;

function pureFunc(): int
{
	return 1;
}

final class Foo
{

	public function pureBase(): int
	{
		return 1 + 1;
	}

	public function transitive(): int
	{
		return $this->pureBase() + pureFunc();
	}

	/** @phpstan-impure */
	public function impureBase(): void
	{
		echo 'x';
	}

	public function callsImpure(): void
	{
		$this->impureBase();
	}

}

function (Foo $foo): void {
	$foo->pureBase();
	$foo->transitive();
	$foo->impureBase();
	$foo->callsImpure();
};
