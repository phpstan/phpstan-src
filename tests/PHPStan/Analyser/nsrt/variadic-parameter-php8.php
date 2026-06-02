<?php // lint >= 8.0

namespace VariadicParameterPHP8;

use function PHPStan\Testing\assertType;

function foo(...$args)
{
	assertType('array<int<0, max>|string, mixed>', $args);
	assertType('mixed', $args['foo']);
	assertType('mixed', $args['bar']);
}

function bar(string ...$args)
{
	assertType('array<int<0, max>|string, string>', $args);
}

function baz(mixed ...$args)
{
	assertType('array<int<0, max>|string, mixed>', $args);
}

