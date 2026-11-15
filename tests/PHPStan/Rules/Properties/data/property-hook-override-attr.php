<?php // lint >= 8.4

namespace PropertyHookOverrideAttr;

class ParentClass
{

	public int $prop {
		get => 1;
	}

}

class Child extends ParentClass
{

	public int $prop {
		get => 2;
	}

	public int $notInParent {
		#[\Override]
		get => 3;
	}

}
