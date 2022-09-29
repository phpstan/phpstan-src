<?php // lint >= 8.1

namespace Bug8078;

class HelloWorld
{
	public function test(): void
	{
		$closure = (static fn (): string => 'evaluated Closure')(...);
	}
}
