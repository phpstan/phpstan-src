<?php

namespace Bug2741Or;

class Foo
{

	function maybeString(): ?string {
		return rand(0, 10) > 4 ? "test" : null;
	}

	function test(): string {
		$foo = $this->maybeString();
		($foo !== null) || ($foo = "");
		return $foo;
	}

	function test2(): void
	{
		$foo = $this->maybeString();
		if (($foo !== null) || ($foo = "")) {

		}
	}

}
