<?php // lint >= 8.4

namespace GetAbstractPropertyHook;

class NonFinalClass
{
	public string $publicProperty;
}

abstract class Foo extends NonFinalClass
{
	abstract public string $publicProperty {
		get;
	}
}
