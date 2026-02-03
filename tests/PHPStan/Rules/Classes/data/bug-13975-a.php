<?php declare(strict_types=1);

namespace Bug13975a;

function foo(): callable
{
	return new class () {
		public function __invoke(): void
		{
		}
	};
}

$foo = foo();

if (\is_object($foo) && method_exists($foo, '__invoke') && !$foo instanceof \Closure) {
	echo 'true';
}
