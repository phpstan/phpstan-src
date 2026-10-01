<?php // lint >= 8.0

declare(strict_types = 1);

namespace TemplateArgumentMultipleSites;

use function PHPStan\Testing\assertType;

/** @template-covariant T */
final class Box
{
	/**
	 * @template U
	 * @param U $value
	 * @return self<U>
	 */
	public static function of(mixed $value): self { return new self(); }

	/**
	 * @template U
	 * @param self<U> $a
	 * @param self<U> $b
	 * @return self<T|U>
	 */
	public function zip(self $a, self $b): self { return new self(); }

	/** @return T */
	public function get(): mixed { throw new \Exception(); }
}

/** @template T */
final class Coll
{
	/** @param T $v */
	public function add(mixed $v): void {}
	/** @return T */
	public function first(): mixed { throw new \Exception(); }
}

/** @template-contravariant T */
final class Sink
{
	/** @param T $v */
	public function accept(mixed $v): void {}
}

/**
 * @template U
 * @param Box<U> $a
 * @param Box<U> $b
 * @return Box<U>
 */
function joinBoxes(Box $a, Box $b): Box { return $a; }

/**
 * @template U
 * @param Coll<U> $a
 * @param Coll<U> $b
 * @return Coll<U>
 */
function joinColls(Coll $a, Coll $b): Coll { return $a; }

/**
 * @template U
 * @param Box<U> $a
 * @param Box<U> $b
 * @return Coll<U>
 */
function collectBoxes(Box $a, Box $b): Coll { return new Coll(); }

/**
 * @template U
 * @param Coll<U> $a
 * @param Coll<U> $b
 * @return Box<U>
 */
function freezeColls(Coll $a, Coll $b): Box { return new Box(); }

/**
 * @template U
 * @param Sink<U> $a
 * @param Sink<U> $b
 * @return Sink<U>
 */
function joinSinks(Sink $a, Sink $b): Sink { return $a; }

/** @param Box<int> $b */
function takesBoxOfInt(Box $b): void {}

/** @template-covariant T */
final class Frozen
{
	/** @param T $value */
	public function __construct(mixed $value) {}
}

/**
 * @template U
 * @param Frozen<U> $a
 * @param Frozen<U> $b
 * @return Frozen<U>
 */
function joinFrozen(Frozen $a, Frozen $b): Frozen { return $a; }

/** @param Coll<int> $c */
function takesCollOfInt(Coll $c): void {}

/** @param Box<int> $b */
function covariantIssue(Box $b): void
{
	$z = Box::of(1)->zip(Box::of(2), Box::of(3));
	$z->get();
	assertType('TemplateArgumentMultipleSites\Box<1|2|3>', $z);

	$z = Box::of(1)->zip(Box::of(2), Box::of(2));
	$z->get();
	assertType('TemplateArgumentMultipleSites\Box<1|2>', $z);

	$z = Box::of(1)->zip(Box::of('a'), Box::of(2));
	assertType("TemplateArgumentMultipleSites\Box<1|2|'a'>", $z);

	$z = Box::of(1)->zip(Box::of(2), $b);
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function covariantAcrossStatements(): void
{
	$a = Box::of(2);
	$b = Box::of(3);
	$z = joinBoxes($a, $b);
	takesBoxOfInt($z);
	assertType('TemplateArgumentMultipleSites\Box<2>', $a);
	assertType('TemplateArgumentMultipleSites\Box<3>', $b);
	assertType('TemplateArgumentMultipleSites\Box<2|3>', $z);
}

function covariantNothingInferred(): void
{
	$a = new Box();
	$b = new Box();
	$z = joinBoxes($a, $b);
	takesBoxOfInt($z);
	assertType('TemplateArgumentMultipleSites\Box<int>', $a);
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function covariantOneSideInferred(): void
{
	$a = new Box();
	$b = Box::of(3);
	$z = joinBoxes($a, $b);
	takesBoxOfInt($z);
	assertType('TemplateArgumentMultipleSites\Box<int>', $a);
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function invariantDown(): void
{
	$a = new Coll();
	$b = new Coll();
	$z = joinColls($a, $b);
	$z->add(1);
	assertType('TemplateArgumentMultipleSites\Coll<1>', $a);
	assertType('TemplateArgumentMultipleSites\Coll<1>', $b);
	assertType('TemplateArgumentMultipleSites\Coll<1>', $z);
}

function invariantUp(): void
{
	$a = new Coll();
	$a->add(1);
	$b = new Coll();
	$b->add('x');
	$z = joinColls($a, $b);
	assertType("TemplateArgumentMultipleSites\Coll<1|'x'>", $a);
	assertType("TemplateArgumentMultipleSites\Coll<1|'x'>", $z);
}

function invariantSend(): void
{
	$a = new Coll();
	$a->add(1);
	$b = new Coll();
	$z = joinColls($a, $b);
	takesCollOfInt($z);
	assertType('TemplateArgumentMultipleSites\Coll<int>', $a);
	assertType('TemplateArgumentMultipleSites\Coll<int>', $b);
	assertType('TemplateArgumentMultipleSites\Coll<int>', $z);
}

function covariantIntoInvariant(): void
{
	$a = new Box();
	$b = new Box();
	$z = collectBoxes($a, $b);
	takesCollOfInt($z);
	assertType('TemplateArgumentMultipleSites\Box<int>', $a);
	assertType('TemplateArgumentMultipleSites\Coll<int>', $z);
}

function covariantIntoInvariantInferred(): void
{
	$a = Box::of(1);
	$b = Box::of(2);
	$z = collectBoxes($a, $b);
	$z->add(3);
	assertType('TemplateArgumentMultipleSites\Box<1>', $a);
	assertType('TemplateArgumentMultipleSites\Coll<1|2|3>', $z);
}

function invariantIntoCovariant(): void
{
	$a = new Coll();
	$a->add(1);
	$b = new Coll();
	$b->add(2);
	$z = freezeColls($a, $b);
	takesBoxOfInt($z);
	assertType('TemplateArgumentMultipleSites\Coll<int>', $a);
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function contravariant(): void
{
	$a = new Sink();
	$b = new Sink();
	$z = joinSinks($a, $b);
	$z->accept(1);
	assertType('TemplateArgumentMultipleSites\Sink<mixed>', $a);
	assertType('TemplateArgumentMultipleSites\Sink<mixed>', $z);
}

/** @param list<int> $items */
function generalizedInLoop(array $items): void
{
	$z = Box::of(1)->zip(Box::of(2), Box::of(3));
	foreach ($items as $item) {
		$z = $z->zip(Box::of($item), Box::of(4));
		$z->get();
	}
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function ternary(bool $c): void
{
	$z = $c ? Box::of(1) : Box::of(1);
	$y = joinBoxes($z, Box::of(2));
	$y->get();
	assertType('TemplateArgumentMultipleSites\Box<1|2>', $y);
}

/** @param Box<int> $ints */
function joinedWithConcrete(Box $ints): void
{
	$a = new Box();
	$z = joinBoxes($a, $ints);
	takesBoxOfInt($z);
	assertType('TemplateArgumentMultipleSites\Box<int>', $a);
	assertType('TemplateArgumentMultipleSites\Box<int>', $z);
}

function covariantNew(): void
{
	$a = new Frozen(1);
	$b = new Frozen('x');
	$z = joinFrozen($a, $b);
	assertType("TemplateArgumentMultipleSites\Frozen<1>", $a);
	assertType("TemplateArgumentMultipleSites\Frozen<1|'x'>", $z);
}
