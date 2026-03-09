<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug13133;

if (PHP_VERSION_ID >= 80300) {
	class Foo {
		public const string BAR = 'bar';
	}
} else {
	class Foo {
		public const BAR = 'bar';
	}
}

echo Foo::BAR;
