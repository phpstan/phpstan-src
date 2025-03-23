<?php // lint >= 8.4

namespace AbstractPrivateHook;

abstract class Foo
{
	abstract private int $i { get; }
	abstract protected int $ii { get; }
	abstract public int $iii { get; }
}
