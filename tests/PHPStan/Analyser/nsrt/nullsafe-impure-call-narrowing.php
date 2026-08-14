<?php declare(strict_types = 1);

namespace NullsafeImpureCallNarrowing;

use function PHPStan\Testing\assertType;

class Dependency
{

	/** @phpstan-impure */
	public function impure(): ?int
	{
		return 1;
	}

}

class Holder
{

	public Dependency $dep;

	public function __construct()
	{
		$this->dep = new Dependency();
	}

}

function notNullComparison(?Holder $a): void
{
	if ($a?->dep->impure() !== null) {
		assertType('NullsafeImpureCallNarrowing\Holder', $a);
	}
}

function truthyContext(?Holder $a): void
{
	if ($a?->dep->impure()) {
		assertType('NullsafeImpureCallNarrowing\Holder', $a);
	}
}

class DataDep
{

	public ?string $label = null;

	/** @var array<int, string> */
	public array $items = [];

}

class DataHolder
{

	public DataDep $dep;

	public function __construct()
	{
		$this->dep = new DataDep();
	}

}

function truthyPropertyFetch(?DataHolder $a): void
{
	if ($a?->dep->label) {
		assertType('NullsafeImpureCallNarrowing\DataHolder', $a);
	}
}

function truthyDimFetch(?DataHolder $a): void
{
	if ($a?->dep->items[0]) {
		assertType('NullsafeImpureCallNarrowing\DataHolder', $a);
	}
}
