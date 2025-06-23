<?php declare(strict_types = 1);

namespace FixWithTabs;

interface FooInterface {
		/** @return Collection<BarI> */
	public function foo(): Collection;
}

interface BarI
{
	
}
class Bar implements BarI {}

/** @template-coveriant TValue */
class Collection {}


class Baz implements FooInterface
{
	/** @return Collection<Bar> */
	public function foo(): Collection
	{
		/** @var Collection<Bar> */
		return new Collection();
	}
}
