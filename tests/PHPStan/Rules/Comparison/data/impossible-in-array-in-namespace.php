<?php // lint >= 8.1

namespace NamespacedFunction;

function in_array($a, $b) {
	echo "$a $b";
}

enum Foo
{

	case ONE;
	case TWO;
	case THREE;

}

function reportedFiniteValueStrict(int $i): void
{
	// Foo::ONE can never be an int, but 1 and 2 can.
	if (in_array($i, [Foo::ONE, 1, 2], true)) {
		echo 'yes';
	}
}
