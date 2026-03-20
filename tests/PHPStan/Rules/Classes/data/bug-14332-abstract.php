<?php declare(strict_types = 1);

namespace Bug14332Abstract;

trait ConcreteTrait
{
	public function doSomething(): void
	{
	}
}

trait AbstractTrait
{
	abstract public function doSomething(): void;
}

// ok - abstract + concrete is allowed
class FooWithAbstractAndConcrete
{
	use ConcreteTrait, AbstractTrait;
}
