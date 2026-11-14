<?php declare(strict_types = 1);

/*
 * instructions: 41.90M
 * cycles: 61.61M
 * memory: 432.0KiB
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
