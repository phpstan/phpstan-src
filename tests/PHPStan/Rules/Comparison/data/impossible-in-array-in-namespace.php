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
	// should not error, as the in_array is namespaced and not the global one
	if (in_array($i, [Foo::ONE, 1, 2], true)) {
		echo 'yes';
	}
}
