<?php declare(strict_types = 1);

namespace Bug14667Methods;

/** @param mixed $row */
function testImplicitMixed($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

function testExplicitMixed(mixed $row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

/** @param object|string $row */
function testObjectOrString($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

function testObject(object $row): void
{
	if (method_exists($row, 'bar')) {
		$row->bar();
	}
}

/** @param mixed $x */
function testMixedChained($x): void
{
	if (method_exists($x, 'getName') && $x->getName() !== null) {
		echo $x->getName();
	}
}
