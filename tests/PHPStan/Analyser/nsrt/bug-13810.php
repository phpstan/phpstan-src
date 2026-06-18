<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13810;

use function PHPStan\Testing\assertType;

function test(): void
{
	static $isSupported;
	$isSupported ??= function (mixed $arg) use (&$isSupported): bool {
		assertType('Closure(mixed): bool', $isSupported);
		return $isSupported($arg);
	};

	assertType('mixed~null', $isSupported);
	$isSupported('foo');
}
