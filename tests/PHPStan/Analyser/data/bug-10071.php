<?php declare(strict_types=1); // onlyif PHP_VERSION_ID >= 80000

namespace Bug10071;

use function PHPStan\Testing\assertType;

class Foo
{
	public ?bool $bar = null;
}


function okIfBar(?Foo $foo = null): void
{
	if ($foo?->bar !== false) {
		assertType(Foo::class . '|null', $foo);
	} else {
		assertType(Foo::class, $foo);
	}

	assertType(Foo::class . '|null', $foo);
}
