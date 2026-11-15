<?php // lint >= 8.0

namespace OverridingPropertyEquivalentTypes;

class X {}
class Y extends X {}

class A
{

	public X|Y $prop;

	public iterable $iterable;

	public object|iterable $objectOrIterable;

	public object|array $objectOrArray;

	public array|\Traversable $arrayOrTraversable;

}

class B extends A
{

	public X $prop;

	public array|\Traversable $iterable;

	public object|array $objectOrIterable;

	public object|iterable $objectOrArray;

	public iterable $arrayOrTraversable;

}
