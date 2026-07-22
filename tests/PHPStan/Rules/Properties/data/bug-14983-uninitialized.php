<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14983Uninitialized;

trait TraitA {
	protected string $property;
}

trait TraitB {
	use TraitA;
}

class ClassA {
	use TraitA;

	public function __construct(protected string $property) {}
}

class ClassB extends ClassA {
	use TraitB;
}
