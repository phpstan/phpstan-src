<?php // lint >= 7.4

namespace Bug3261;

class A
{
}

class B extends A {}

function (): void {
	/** @var A[] $a */
	$a = [];

	array_filter(
		$a,
		fn (A $a) => $a instanceof B
	);

};
