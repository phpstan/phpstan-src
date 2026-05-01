<?php // lint >= 8.4

namespace Bug14564;

interface A {
	/** @var array<string> */
	public array $test { get; }

	/** @var non-empty-string */
	public string $nonEmptyString { get; }

	/** @var int<1,max> */
	public int $positive { get; }
}

// Regular properties inherit PHPDoc types from interface - works correctly
class B implements A {

	public array $test = [ 1 ];

	public string $nonEmptyString = '';

	public int $positive = -1;

}

// Promoted properties should also inherit PHPDoc types from interface
class C implements A {

	public function __construct(

		public array $test,

		public string $nonEmptyString,

		public int $positive,

	) { }

}

// This works because types are explicitly annotated
class D implements A {

	public function __construct(

		/** @var array<string> */
		public array $test,

		/** @var non-empty-string */
		public string $nonEmptyString,

		/** @var int<1,max> */
		public int $positive,

	) { }

}

// Inheritance from parent class
class ParentClass {
	/** @var array<string> */
	public array $items;

	/** @var non-empty-string */
	public string $name;
}

class ChildWithPromoted extends ParentClass {

	public function __construct(
		public array $items,
		public string $name,
	) { }

}

// Inheritance from abstract class
abstract class AbstractBase {
	/** @var array<int, string> */
	public array $data;
}

class ConcreteWithPromoted extends AbstractBase {

	public function __construct(
		public array $data,
	) { }

}

// Constructor @param should still override inherited type
class WithExplicitParam implements A {

	/**
	 * @param list<string> $test
	 * @param non-empty-string $nonEmptyString
	 * @param positive-int $positive
	 */
	public function __construct(
		public array $test,
		public string $nonEmptyString,
		public int $positive,
	) { }

}
