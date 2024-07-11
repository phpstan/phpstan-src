<?php declare(strict_types = 1);

// no namespace

exit(0);

echo 1;

function bug11179Foo(string $p): string
{
	\PHPStan\dumpType($p);
	return "";
}
