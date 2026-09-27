<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureByRefUsesAtInvocationRule;

class Foo
{

	public function modifiedBeforeInvocation(): void
	{
		$y = 1;
		$d = function () use (&$y): void {
			strlen($y);
		};
		$y = 'b';
		$d();
	}

	public function invokedWithInt(): void
	{
		$y = 'a';
		$d = function () use (&$y): void {
			strlen($y);
		};
		$y = 1;
		$d();
	}

}
