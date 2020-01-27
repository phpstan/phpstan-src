<?php

namespace ExtDs;

use Ds\Map;
use function PHPStan\Analyser\assertType;

class A
{
}

class B
{
}

class Foo
{
	public function mapMerge() : void
	{
		$a = new Map([1 => new A()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->merge(['2' => new B()]));
	}

	public function mapUnion() : void
	{
		$a = new Map([1 => new A()]);
		$b = new Map(['2' => new B()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->union($b));
	}

	public function mapXor() : void
	{
		$a = new Map([1 => new A()]);
		$b = new Map(['2' => new B()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->xor($b));
	}

	public function setMerge() : void
	{
		$a = new Set(new A());

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->merge([new B()]));
	}

	public function setUnion() : void
	{
		$a = new Set(new A());
		$b = new Set(new B());

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->union($b));
	}

	public function setXor() : void
	{
		$a = new Set(new A());
		$b = new Set(new B());

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->xor($b));
	}
}
