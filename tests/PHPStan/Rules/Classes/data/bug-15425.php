<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15425;

if (PHP_VERSION_ID < 80400) {
	\PHPStan\dumpType(PHP_VERSION_ID);

	final class Foo
	{
		public function __construct(public ?string $message = null)
		{
		}
	}
}
