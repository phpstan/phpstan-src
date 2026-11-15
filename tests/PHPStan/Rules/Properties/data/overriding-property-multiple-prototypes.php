<?php // lint >= 8.4

namespace OverridingPropertyMultiplePrototypes;

interface HasGet
{

	public int $prop { get; }

}

interface HasSet
{

	public int $prop { set; }

}

interface HasStringGet
{

	public string $prop { get; }

}

class GetOnly implements HasGet, HasSet
{

	public int $prop {
		get => 1;
	}

}

class WrongType implements HasGet, HasStringGet
{

	public int $prop;

}

class ParentWithProperty
{

	public int $prop {
		get => 1;
	}

}

class ChildImplementingInterface extends ParentWithProperty implements HasSet
{

	public int $prop {
		get => 2;
	}

}

class ParentImplementingInterface implements HasGet
{

	public int $prop = 1;

}

class ChildOfParentImplementingInterface extends ParentImplementingInterface
{

	public int $prop = 2;

}

interface ExtendsBoth extends HasGet, HasSet
{

}

class ImplementsExtendsBoth implements ExtendsBoth
{

	public int $prop {
		set {
		}
	}

}

class Valid implements HasGet, HasSet
{

	public int $prop;

}
