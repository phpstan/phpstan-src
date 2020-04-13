<?php

namespace ExtDs;

use Ds\Map;
use Ds\Set;
use function PHPStan\Analyser\assertType;

class A
{
}

class B
{
}

class Foo
{
	/** @param Map<int, int> $a */
	public function mapGet(Map $a) : void
	{
		assertType('int', $a->get(1));
	}

	/** @param Map<int, int> $a */
	public function mapGetWithDefaultValue(Map $a) : void
	{
		assertType('int|null', $a->get(1, null));
	}

	/** @param Map<int, int|string> $a */
	public function mapGetUnionType(Map $a) : void
	{
		assertType('int|string', $a->get(1));
	}

	/** @param Map<int, int|string> $a */
	public function mapGetUnionTypeWithDefaultValue(Map $a) : void
	{
		assertType('int|string|null', $a->get(1, null));
	}

	/** @param Map<int, int|string> $a */
	public function mapRemoveUnionType(Map $a) : void
	{
		assertType('int|string', $a->remove(1));
	}

	/** @param Map<int, int|string> $a */
	public function mapRemoveUnionTypeWithDefaultValue(Map $a) : void
	{
		assertType('int|string|null', $a->remove(1, null));
	}

	public function mapMerge() : void
	{
		$a = new Map([1 => new A()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->merge(['a' => new B()]));
	}

	public function mapUnion() : void
	{
		$a = new Map([1 => new A()]);
		$b = new Map(['a' => new B()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->union($b));
	}

	public function mapXor() : void
	{
		$a = new Map([1 => new A()]);
		$b = new Map(['a' => new B()]);

		assertType('Ds\Map<int|string, ExtDs\A|ExtDs\B>', $a->xor($b));
	}

	public function setMerge() : void
	{
		$a = new Set([new A()]);

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->merge([new B()]));
	}

	public function setUnion() : void
	{
		$a = new Set([new A()]);
		$b = new Set([new B()]);

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->union($b));
	}

	public function setXor() : void
	{
		$a = new Set([new A()]);
		$b = new Set([new B()]);

		assertType('Ds\Set<ExtDs\A|ExtDs\B>', $a->xor($b));
	}
}
