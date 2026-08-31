<?php declare(strict_types = 1);

namespace VariableVariableDisjunction;

class Foo
{

	/** @var array<string, string> */
	public array $map = [];

	public function doFoo(string $n, ?Foo $o): void
	{
		$$n = $o;
		if ($$n !== null && $$n->map !== [] || $$n === null) {
			echo count($$n->map);
		}
	}

}
