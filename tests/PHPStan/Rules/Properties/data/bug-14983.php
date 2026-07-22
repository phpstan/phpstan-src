<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14983;

trait TraitA {
	protected readonly string $property;
}

trait TraitB {
	use TraitA;
}

class ClassA {
	use TraitA;

	public function __construct(protected readonly string $property) {}
}

class ClassB extends ClassA {
	use TraitB;
}

trait GrandTrait {
	use TraitA;
}

class GrandParent1 {
	use TraitA;

	public function __construct(protected readonly string $property) {}
}

class Parent1 extends GrandParent1 {}

class Child1 extends Parent1 {
	use GrandTrait;
}

class UninitializedFromTrait {
	use TraitA;
}
