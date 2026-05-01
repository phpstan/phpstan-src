<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug14564Nsrt;

use function PHPStan\Testing\assertType;

interface A {
	/** @var array<string> */
	public array $test { get; }

	/** @var non-empty-string */
	public string $nonEmptyString { get; }

	/** @var int<1,max> */
	public int $positive { get; }
}

// Promoted properties inherit from interface
class B implements A {

	public function __construct(

		public array $test,

		public string $nonEmptyString,

		public int $positive,

	) { }

}

function test(B $b): void {
	assertType('array<string>', $b->test);
	assertType('non-empty-string', $b->nonEmptyString);
	assertType('int<1, max>', $b->positive);
}

// Promoted properties inherit from parent class
class ParentClass {
	/** @var array<string> */
	public array $items;
}

class ChildWithPromoted extends ParentClass {

	public function __construct(
		public array $items,
	) { }

}

function test2(ChildWithPromoted $c): void {
	assertType('array<string>', $c->items);
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

function test3(WithParam $w): void {
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

function test4(WithVar $w): void {
	assertType('list<string>', $w->test);
	assertType('non-empty-string', $w->nonEmptyString);
	assertType('int<1, max>', $w->positive);
}

// Multi-level inheritance
abstract class AbstractBase {
	/** @var array<int, string> */
	public array $data;
}

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

function test6(StringContainer $c): void {
	assertType('string', $c->value);
}
