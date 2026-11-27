<?php declare(strict_types = 1);

/*
 * instructions: 45.72M
 * cycles: 66.63M
 * memory: 299.8KiB
 */

namespace MethodExistsLoopRepro;

class Foo
{
}

function test(): void
{
	$class = Foo::class;
	foreach (['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12'] as $method) {
		if (method_exists($class, $method)) {
		}
	}
}
