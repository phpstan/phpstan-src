<?php declare(strict_types = 1);

namespace TraitCycleMembers;

trait TraitA
{
	use TraitB;

	public int $a = 1;

	public function fromA(): int
	{
		return $this->b;
	}
}

trait TraitB
{
	use TraitA;

	public int $b = 2;

	public function fromB(): int
	{
		return $this->a;
	}
}

class UsesCycle
{
	use TraitA;
}
