<?php // lint >= 8.1

namespace PHPStan\Rules\Pure\data\Bug13201;

enum Foo: string
{

	case Bar     = 'bar';
	case Unknown = 'unknown';

}

/**
 * @pure
 */
function createWithFallback(string $type): Foo
{
	return Foo::tryFrom($type) ?? Foo::Unknown;
}
