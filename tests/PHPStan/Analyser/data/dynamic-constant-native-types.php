<?php // lint >= 8.3

namespace DynamicConstantNativeTypes;

use function PHPStan\Testing\assertType;

final class Foo
{

	public const int FOO = 123;
	public const int|string BAR = 123;

}

function (Foo $foo): void {
	assertType('int', Foo::FOO);
	assertType('int|string', Foo::BAR);
	assertType('int', $foo::FOO);
	assertType('int|string', $foo::BAR);
};
