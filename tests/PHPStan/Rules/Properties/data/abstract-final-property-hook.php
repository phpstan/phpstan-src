<?php // lint >= 8.4

namespace AbstractFinalHook;

abstract class User
{
	abstract public string $foo {
		final get;
	}
}
