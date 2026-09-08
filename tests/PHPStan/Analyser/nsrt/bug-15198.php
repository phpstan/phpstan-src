<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15198;

use Exception;
use function PHPStan\Testing\assertType;

/** @template T */
class Foo
{

	/** @param T $value */
	public function __construct(
		public mixed $value,
	)
	{
	}

}

/** @template T */
interface FooInterface
{

}

/** @template-covariant T */
class CovariantFoo
{

}

/** @template-contravariant T */
class ContravariantFoo
{

}

/** @template T */
abstract class Option
{

}

/** @extends Option<mixed> */
final class None extends Option
{

}

function original(): void
{
	try {
		/** @var mixed */
		$bar = 'bar';
		$foo = new Foo($bar);
	} catch (Exception $e) {
		$foo = new Foo(123);
	}

	assertType('Bug15198\Foo<mixed>', $foo);
}

function ifElse(bool $c, mixed $bar): void
{
	if ($c) {
		$foo = new Foo($bar);
	} else {
		$foo = new Foo(123);
	}

	assertType('Bug15198\Foo<mixed>', $foo);
}

function ifElseReversed(bool $c, mixed $bar): void
{
	if ($c) {
		$foo = new Foo(123);
	} else {
		$foo = new Foo($bar);
	}

	assertType('Bug15198\Foo<mixed>', $foo);
}

/**
 * @param Foo<mixed>|Foo<int> $a
 * @param Foo<int>|Foo<mixed> $b
 * @param CovariantFoo<mixed>|CovariantFoo<int> $c
 * @param CovariantFoo<int>|CovariantFoo<mixed> $d
 * @param ContravariantFoo<mixed>|ContravariantFoo<int> $e
 * @param ContravariantFoo<int>|ContravariantFoo<mixed> $f
 * @param FooInterface<mixed>|FooInterface<int> $g
 * @param FooInterface<int>|FooInterface<mixed> $h
 * @param class-string<Foo<mixed>>|class-string<Foo<int>> $i
 * @param class-string<Foo<int>>|class-string<Foo<mixed>> $j
 */
function unions($a, $b, $c, $d, $e, $f, $g, $h, $i, $j): void
{
	assertType('Bug15198\Foo<mixed>', $a);
	assertType('Bug15198\Foo<mixed>', $b);
	assertType('Bug15198\CovariantFoo<mixed>', $c);
	assertType('Bug15198\CovariantFoo<mixed>', $d);
	assertType('Bug15198\ContravariantFoo<int>', $e);
	assertType('Bug15198\ContravariantFoo<int>', $f);
	assertType('Bug15198\FooInterface<mixed>', $g);
	assertType('Bug15198\FooInterface<mixed>', $h);
	assertType('class-string<Bug15198\Foo<mixed>>', $i);
	assertType('class-string<Bug15198\Foo<mixed>>', $j);
}

/**
 * @param Foo<mixed>&Foo<int> $a
 * @param Foo<int>&Foo<mixed> $b
 */
function intersections($a, $b): void
{
	assertType('Bug15198\Foo<int>', $a);
	assertType('Bug15198\Foo<int>', $b);
}

/**
 * A class name written without type arguments has them resolved to the template
 * bounds, so `None` stays compatible with `Option<string>`.
 *
 * @param None|Option<string> $a
 * @param Option<string>|None $b
 */
function unparameterizedSubclass($a, $b): void
{
	assertType('Bug15198\Option<string>', $a);
	assertType('Bug15198\Option<string>', $b);
}
