<?php // lint >= 8.0

namespace VariableCloningNullsafe;

class Bar
{
	public \stdClass $foo;
}

function doFoo(?Bar $bar) {
	clone $bar?->foo;
};
