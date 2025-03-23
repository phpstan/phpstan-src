<?php // lint >= 8.4

namespace AbstractFinalHook;

abstract class User
{
	abstract public string $foo {
		final get;
	}
}

abstract class Foo
{
	abstract public int $i { final get { return 1;} set; }
}
