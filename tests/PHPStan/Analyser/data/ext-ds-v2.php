<?php

namespace ExtDsV2;

use Ds\Heap;
use Ds\Map;
use Ds\Seq;
use Ds\Set;
use function PHPStan\Testing\assertType;

class A
{
}

class B
{
}

/** @param \Ds\Set<string> $s */
function f(\Ds\Set $s): void
{
	assertType('list<string>', $s->toArray());
}

class Foo
{

	/**
	 * @param Seq<int> $seq
	 * @param Set<string> $set
	 * @param Heap<float> $heap
	 * @param Map<string, int> $map
	 */
	public function toArray(Seq $seq, Set $set, Heap $heap, Map $map): void
	{
		assertType('list<int>', $seq->toArray());
		assertType('list<string>', $set->toArray());
		assertType('list<float>', $heap->toArray());
		assertType('array<string, int>', $map->toArray());

		assertType('list<int>', $seq->jsonSerialize());
		assertType('list<string>', $set->jsonSerialize());
		assertType('list<float>', $heap->jsonSerialize());

		assertType('list<int>', $seq->__serialize());
		assertType('list<float>', $heap->__serialize());
	}

	/** @param Map<int, int|string> $a */
	public function mapGetRemove(Map $a): void
	{
		assertType('int|string', $a->get(1));
		assertType('int|string|null', $a->get(1, null));
		assertType('int|string', $a->remove(1));
		assertType('int|string|null', $a->remove(1, null));
	}

	public function mapMergeUnionXor(): void
	{
		$a = new Map([1 => new A()]);
		$b = new Map(['a' => new B()]);

		assertType('Ds\Map<1|\'a\', ExtDsV2\A|ExtDsV2\B>', $a->merge(['a' => new B()]));
		assertType('Ds\Map<1|\'a\', ExtDsV2\A|ExtDsV2\B>', $a->union($b));
		assertType('Ds\Map<1|\'a\', ExtDsV2\A|ExtDsV2\B>', $a->xor($b));
	}

	public function setMergeUnionXor(): void
	{
		$a = new Set([new A()]);
		$b = new Set([new B()]);

		assertType('Ds\Set<ExtDsV2\A|ExtDsV2\B>', $a->merge([new B()]));
		assertType('Ds\Set<ExtDsV2\A|ExtDsV2\B>', $a->union($b));
		assertType('Ds\Set<ExtDsV2\A|ExtDsV2\B>', $a->xor($b));
	}

	public function seqMerge(): void
	{
		$a = new Seq([new A()]);

		assertType('Ds\Seq<ExtDsV2\A|ExtDsV2\B>', $a->merge([new B()]));
	}

	/** @param Set<int> $set */
	public function reduce(Set $set): void
	{
		assertType('int', $set->reduce(static fn (int $carry, int $value): int => $carry + $value, 0));
	}

}
