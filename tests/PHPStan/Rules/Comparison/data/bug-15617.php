<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15617;

function doFoo(
	object $unknownObject
):int {
	if ($unknownObject) {
		return 0;
	}

	return 1;
}

class A {}

var_dump(doFoo(new \GMP(0))); // int(1)
var_dump(doFoo(new A())); // int(0)

interface Bar {}

/**
 * @template T of object
 * @param T $template
 * @param object{} $shape
 */
function doBar(
	\Countable $countable,
	\Stringable $stringable,
	\Traversable $traversable,
	\ArrayAccess $arrayAccess,
	$template,
	$shape,
	A $a,
	Bar $bar
): void {
	if ($countable) {
	}
	if ($stringable) {
	}
	if ($traversable) {
	}
	if ($arrayAccess) {
	}
	if ($template) {
	}
	if ($shape) {
	}
	if ($a) {
	}
	if ($bar) {
	}
}
