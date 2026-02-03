<?php declare(strict_types=1);

namespace Bug13975b;

class X {}

function foo(): callable
{
	return new class () {
		public function __invoke(): void
		{
		}
	};
}

$foo = foo();

$class = \Closure::class;
if (rand(0, 1)) {
	$class = X::class;
}

if (\is_object($foo) && method_exists($foo, '__invoke') && !$foo instanceof $class) {
	echo 'true';
}
