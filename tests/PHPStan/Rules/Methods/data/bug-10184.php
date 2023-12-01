<?php

namespace Bug10184;

interface FooI {}
class Bar implements FooI {}

/** @template TValue */
class Collection {}

/** @template TValue of FooI */
trait FooTrait
{
	/** @return Collection<TValue> */
	abstract public function foo(): Collection;
}

class Baz
{
	/** @use FooTrait<Bar> */
	use FooTrait;

	/** @return Collection<Bar> */
	public function foo(): Collection
	{
		/** @var Collection<Bar> */
		return new Collection();
	}
}
