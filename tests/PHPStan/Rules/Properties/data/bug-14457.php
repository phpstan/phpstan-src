<?php // lint >= 8.4

namespace Bug14457Property;

abstract class ParentClass {
	public int $bar { get => 42; }
}

abstract class ChildClass extends ParentClass {
	abstract public int $bar { get; }
}

// OK: abstract hook overriding abstract hook
abstract class AbstractParent {
	abstract public int $prop { get; }
}

abstract class AbstractChild extends AbstractParent {
	abstract public int $prop { get; }
}

// OK: concrete hook overriding abstract hook
abstract class ConcreteChild extends AbstractParent {
	public int $prop { get => 1; }
}

// OK: concrete hook overriding concrete hook
abstract class ConcreteParent {
	public int $val { get => 1; }
}

abstract class ConcreteChild2 extends ConcreteParent {
	public int $val { get => 2; }
}

// abstract set hook overriding non-abstract set hook
abstract class SetParent {
	public int $setProp { set => $value; }
}

abstract class SetChild extends SetParent {
	abstract public int $setProp { set; }
}

// both get and set hooks abstract overriding non-abstract
abstract class BothParent {
	public int $bothProp {
		get => 1;
		set => $value;
	}
}

abstract class BothChild extends BothParent {
	abstract public int $bothProp { get; set; }
}
