<?php // lint >= 8.4

namespace OverridingPropertyHooksMissingOverride;

class ParentClass
{

	public int $both {
		get => 1;
		set => $value;
	}

	public int $getOnly {
		get => 1;
	}

	public int $backed = 1;

}

class Child extends ParentClass
{

	public int $both {
		#[\Override]
		get => 2;
		set => $value;
	}

	public int $getOnly {
		get => 2;
	}

	public int $backed {
		get => 2;
	}

}

interface HasHook
{

	public int $prop { get; }

}

class ImplementsInterface implements HasHook
{

	public int $prop {
		get => 1;
	}

}
