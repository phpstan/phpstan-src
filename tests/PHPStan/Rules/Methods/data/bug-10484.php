<?php

namespace Bug10484;

enum Foo {
	case Bar;
}

class Test {
	/**
	 * @param \Ds\Set<Foo> $foos
	 */
	public function bar(\Ds\Set $foos): void
	{
	}

	public function test(): void
	{
		$this->bar(new \Ds\Set([Foo::Bar]));
	}
}
