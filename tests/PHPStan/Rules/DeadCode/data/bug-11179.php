<?php declare(strict_types = 1);

namespace Bug11179;

exit(0);

function foo(string $p): string
{
	\PHPStan\dumpType($p);
	return "";
}

__halt_compiler();
foo
