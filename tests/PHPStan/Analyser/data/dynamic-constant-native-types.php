<?php // lint >= 8.3

namespace DynamicConstantNativeTypes;

final class Foo
{

	public const int FOO = 123;
	public const int|string BAR = 123;

}

function (Foo $foo): void {
	die;
};
