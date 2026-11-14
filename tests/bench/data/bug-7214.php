<?php declare(strict_types = 1);

/*
 * instructions: 41.79M
 * cycles: 61.44M
 * memory: 348.3KiB
 */

namespace Bug7214;

trait foo {
	public function getFoo()
	{
		return new class {
			use foo;
		};
	}
}

class HelloWorld
{
	use foo;
}

function(): void {
	var_dump((new HelloWorld())->getFoo()->getFoo());
};
