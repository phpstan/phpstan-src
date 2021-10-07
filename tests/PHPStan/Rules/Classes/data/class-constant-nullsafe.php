<?php // lint >= 8.0

namespace ClassConstantNullsafeNamespace;

class Foo {
	public const LOREM = 'lorem';

}
class Bar
{
	public Foo $foo;
}

function doFoo(?Bar $bar)
{
	$bar?->foo::LOREM;
}
