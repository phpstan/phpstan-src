<?php // lint >= 8.0

namespace Bug14457;

abstract class ParentClass {
	public function foo(): int {
		return 42;
	}
}

abstract class ChildClass extends ParentClass {
	abstract public function foo(): int;
}

// OK: abstract method overriding abstract method
abstract class AbstractParent {
	abstract public function bar(): int;
}

abstract class AbstractChild extends AbstractParent {
	abstract public function bar(): int;
}

// OK: non-abstract method overriding non-abstract method
abstract class ConcreteParent {
	public function baz(): int {
		return 1;
	}
}

abstract class ConcreteChild extends ConcreteParent {
	public function baz(): int {
		return 2;
	}
}

// OK: non-abstract method overriding abstract method (implementing it)
abstract class AbstractParent2 {
	abstract public function qux(): int;
}

abstract class ConcreteChild2 extends AbstractParent2 {
	public function qux(): int {
		return 1;
	}
}

// abstract static method overriding non-abstract static method
abstract class StaticParent {
	public static function staticMethod(): int {
		return 1;
	}
}

abstract class StaticChild extends StaticParent {
	abstract public static function staticMethod(): int;
}

// abstract protected method overriding non-abstract protected method
abstract class ProtectedParent {
	protected function protectedMethod(): int {
		return 1;
	}
}

abstract class ProtectedChild extends ProtectedParent {
	abstract protected function protectedMethod(): int;
}

// multiple levels of inheritance
abstract class GrandParent_ {
	public function inherited(): int {
		return 1;
	}
}

abstract class Parent_ extends GrandParent_ {
}

abstract class Child_ extends Parent_ {
	abstract public function inherited(): int;
}

// abstract constructor overriding non-abstract constructor
abstract class ConstructorParent {
	public function __construct() {
	}
}

abstract class ConstructorChild extends ConstructorParent {
	abstract public function __construct();
}

// OK: abstract constructor overriding abstract constructor
abstract class AbstractConstructorParent {
	abstract public function __construct();
}

abstract class AbstractConstructorChild extends AbstractConstructorParent {
	abstract public function __construct();
}
