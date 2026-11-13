<?php declare(strict_types = 1);

/*
 * instructions: 52.18M
 * cycles: 73.22M
 * memory: 37.02MiB
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
