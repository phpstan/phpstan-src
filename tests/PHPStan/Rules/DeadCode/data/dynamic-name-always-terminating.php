<?php declare(strict_types = 1);

namespace UnreachableDynamicName;

class Foo
{

	public function doFoo(): void
	{
	}

	public static function doBar(): void
	{
	}

}

function dynamicMethodName(Foo $foo): void
{
	$foo->{exit()}();
	echo 'unreachable';
}

function dynamicStaticMethodName(): void
{
	Foo::{exit()}();
	echo 'unreachable';
}
