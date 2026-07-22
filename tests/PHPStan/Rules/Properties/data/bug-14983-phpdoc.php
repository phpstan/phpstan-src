<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14983PhpDoc;

trait TraitA {
	/** @readonly */
	protected string $property;
}

trait TraitB {
	use TraitA;
}

class ClassA {
	use TraitA;

	public function __construct(
		/** @readonly */
		protected string $property,
	) {}
}

class ClassB extends ClassA {
	use TraitB;
}

class UninitializedFromTrait {
	use TraitA;
}
