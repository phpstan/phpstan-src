<?php

namespace Bug4471;

abstract class Foo
{

}

interface Bar
{

}

function (Foo $foo, Bar $bar, Baz $baz): void {
	new $foo;
	new $bar;
	new $foo(1);
	new $baz;
};

function (): void {
	$foo = Foo::class;
	new $foo;

	$bar = Bar::class;
	new $bar;
};
