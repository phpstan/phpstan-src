<?php declare(strict_types = 1);

namespace CheckTypeFunctionCallReasons;

final class Foo
{

}

interface Bar
{

}

class Baz
{

}

function doFoo(Foo $foo, Baz $baz): void
{
	// Foo is final and does not implement interface Bar -> always false, with a reason
	if (is_a($foo, Bar::class)) {
		echo 'never';
	}

	// two unrelated classes -> always false, with a reason
	if (is_a($baz, Foo::class)) {
		echo 'never';
	}
}
