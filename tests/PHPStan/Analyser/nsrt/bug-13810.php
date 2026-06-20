<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13810;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	static $isSupported;
	assertType('mixed', $isSupported);
	$isSupported ??= function (mixed $arg) use (&$isSupported): bool {
		assertType('Closure(mixed): bool', $isSupported);
		return $isSupported($arg);
	};

	assertType('mixed~null', $isSupported);
	$isSupported('foo');
}

function doBar($isSupported): void
{
	assertType('mixed', $isSupported);
	$isSupported ??= function (mixed $arg) use (&$isSupported): bool {
		assertType('Closure(mixed): bool', $isSupported);
		return $isSupported($arg);
	};

	assertType('mixed~null', $isSupported);
	$isSupported('foo');
}

function doFooBar(): void
{
	$isSupported = null;
	assertType('null', $isSupported);
	$isSupported ??= function (mixed $arg) use (&$isSupported): bool {
		assertType('Closure(mixed): bool', $isSupported);
		return $isSupported($arg);
	};

	assertType('Closure(mixed): bool', $isSupported);
	$isSupported('foo');
}
