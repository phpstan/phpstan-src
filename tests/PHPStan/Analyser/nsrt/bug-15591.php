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
