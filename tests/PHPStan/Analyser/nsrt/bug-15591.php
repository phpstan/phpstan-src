<?php declare(strict_types = 1);

namespace Bug15591;

use function PHPStan\Testing\assertType;

class Foo
{
}

function test(): void
{
	$class = Foo::class;
	foreach (['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12'] as $method) {
		if (method_exists($class, $method)) {
		}
	}
	assertType("'Bug15591\\\\Foo'", $class);
}

function testStraightLine(): void
{
	$class = Foo::class;
	if (method_exists($class, 'm1')) {
		assertType("'Bug15591\\\\Foo'&hasMethod(m1)", $class);
	}
	assertType("'Bug15591\\\\Foo'", $class);
	if (method_exists($class, 'm2')) {
		assertType("'Bug15591\\\\Foo'&hasMethod(m2)", $class);
	}
	assertType("'Bug15591\\\\Foo'", $class);
}

function testProperty(): void
{
	$class = Foo::class;
	if (property_exists($class, 'p1')) {
	}
	if (property_exists($class, 'p2')) {
	}
	assertType("'Bug15591\\\\Foo'", $class);
}

/**
 * @template T
 * @template TString of string
 * @template TBool of bool
 * @param T $x
 * @param TString $s
 * @param TBool $b
 */
function testRefinedConstantKeepsOtherConstants($x, $s, $b): void
{
	assertType('2|(1&T (function Bug15591\testRefinedConstantKeepsOtherConstants(), argument))', $x === 1 ? $x : 2);
	assertType("'b'|('a'&TString of string (function Bug15591\\testRefinedConstantKeepsOtherConstants(), argument))", $s === 'a' ? $s : 'b');
	assertType('(TBool of bool (function Bug15591\testRefinedConstantKeepsOtherConstants(), argument)&true)|false', $b === true ? $b : false);
}

function testAlreadyRefined(): void
{
	$class = Foo::class;
	if (method_exists($class, 'm1')) {
		foreach (['m2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12', 'm13'] as $method) {
			if (method_exists($class, $method)) {
			}
		}
		assertType("'Bug15591\\\\Foo'&hasMethod(m1)", $class);
	}
}
