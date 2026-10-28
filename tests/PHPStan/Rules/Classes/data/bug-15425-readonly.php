<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15425Readonly;

if (PHP_VERSION_ID < 80400) {
	final readonly class Foo
	{
	}
}
