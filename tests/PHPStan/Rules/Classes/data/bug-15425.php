<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15425;

if (PHP_VERSION_ID < 80400) {
	final class Foo
	{
		public function __construct(public ?string $message = null)
		{
		}
	}
}

if (PHP_VERSION_ID < 80000) {
	final class Bar
	{
		public function __construct(public ?string $message = null)
		{
		}
	}
}
