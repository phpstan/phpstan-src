<?php declare(strict_types = 1);

namespace Bug8082TraitCycle;

trait TraitA
{
	use TraitB;
}

trait TraitB
{
	use TraitA;
}

class Foo
{
	use TraitA;
}
