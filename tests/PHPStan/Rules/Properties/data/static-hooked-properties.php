<?php declare(strict_types=1); // lint >= 8.4

namespace StaticHookedProperties;

class HelloWorld
{
	public static string $foo {
		get => $this->foo;
		set => $this->foo = $value;
	}
}

abstract class HiWorld
{
	public static string $foo {
		get => 'dummy';
	}
}
