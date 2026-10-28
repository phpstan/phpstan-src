<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15425Range;

if (PHP_VERSION_ID < 80300) {
	final class Foo
	{
		public function __construct(public ?string $message = null)
		{
		}
	}
}
