<?php declare(strict_types = 1);

namespace CircularReferences;

trait TraitUsingSelf
{

	use TraitUsingSelf;

}

trait FirstTraitInCycle
{

	use SecondTraitInCycle;

}

trait SecondTraitInCycle
{

	use FirstTraitInCycle;

}

class UsesTraitUsingSelf
{

	use TraitUsingSelf;

}

class UsesTraitCycle
{

	use FirstTraitInCycle;

}

class FirstClassInCycle extends SecondClassInCycle
{

}

class SecondClassInCycle extends FirstClassInCycle
{

}
