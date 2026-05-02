<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug14564;

use function PHPStan\Testing\assertType;

interface A {
	/** @var array<string> */
	public array $test { get; }

	/** @var non-empty-string */
	public string $nonEmptyString { get; }

	/** @var int<1,max> */
	public int $positive { get; }
}

// Regular properties inherit PHPDoc types from interface
class B implements A {

	public array $test = [ 1 ];

	public string $nonEmptyString = '';

	public int $positive = -1;

}

// Promoted properties inherit from interface
class C implements A {

	public function __construct(

		public array $test,

		public string $nonEmptyString,

		public int $positive,

	) { }

}

function test(C $c): void {
	assertType('array<string>', $c->test);
	assertType('non-empty-string', $c->nonEmptyString);
	assertType('int<1, max>', $c->positive);
}

// Explicit @var on promoted property
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

function test2(D $d): void {
	assertType('array<string>', $d->test);
	assertType('non-empty-string', $d->nonEmptyString);
	assertType('int<1, max>', $d->positive);
}

// Promoted properties inherit from parent class
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

function test3(ChildWithPromoted $c): void {
	assertType('array<string>', $c->items);
	assertType('non-empty-string', $c->name);
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

function test4(ConcreteWithPromoted $c): void {
	assertType('array<int, string>', $c->data);
}

// Multi-level inheritance
class Middle extends AbstractBase {
}

class GrandchildWithPromoted extends Middle {

	public function __construct(
		public array $data,
	) { }

}

function test5(GrandchildWithPromoted $g): void {
	assertType('array<int, string>', $g->data);
}

// Constructor @param overrides inherited type
class WithParam implements A {

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

function test6(WithParam $w): void {
	assertType('list<string>', $w->test);
	assertType('non-empty-string', $w->nonEmptyString);
	assertType('int<1, max>', $w->positive);
}

// Explicit @var on promoted property overrides inherited type
class WithVar implements A {

	public function __construct(
		/** @var list<string> */
		public array $test,
		/** @var non-empty-string */
		public string $nonEmptyString,
		/** @var int<1,max> */
		public int $positive,
	) { }

}

function test7(WithVar $w): void {
	assertType('list<string>', $w->test);
	assertType('non-empty-string', $w->nonEmptyString);
	assertType('int<1, max>', $w->positive);
}

// Generic interface
/**
 * @template T
 */
interface Container {
	/** @var T */
	public mixed $value { get; }
}

/** @implements Container<string> */
class StringContainer implements Container {

	public function __construct(
		public mixed $value,
	) { }

}

function test8(StringContainer $c): void {
	assertType('string', $c->value);
}

// Private property in parent should not conflict with child's property
class FooWithPrivate {
	/** @var array<string> */
	private array $items = [];
}

class BarWithPromoted extends FooWithPrivate {

	public function __construct(
		public array $items,
	) {
		parent::__construct();
	}

}

function test9(BarWithPromoted $b): void {
	assertType('array', $b->items);
}
