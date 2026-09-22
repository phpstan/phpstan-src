<?php // lint >= 8.3

namespace DynamicClassConstantFetchPhpVersions;

class Foo
{

	public const BAR = 'bar';

}

function doFoo(string $name): void
{
	if (PHP_VERSION_ID >= 80300) {
		echo Foo::{$name};
	}

	if (PHP_VERSION_ID < 80300) {
		echo Foo::{$name};
	}

	echo Foo::{$name};
}
