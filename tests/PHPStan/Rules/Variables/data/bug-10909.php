<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10909;

match(rand(1, 3)) {
	1       => $foo = 'foo',
	2       => $foo = 'bar',
	default => $foo = null,
};

echo $foo;
