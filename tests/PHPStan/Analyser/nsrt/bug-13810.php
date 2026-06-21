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

class HelloWorld
{
	public function setValue(mixed $value): void
	{
		/** @var ?callable $isSupported */
		static $isSupported = null;
		$isSupported ??= function(mixed $arg) use (&$isSupported): bool {
			if (is_array($arg)) {
				foreach($arg as $value) {
					if (!$isSupported($value)) {
						return false;
					}
				}
				return true;
			}
			return is_string($arg);
		};
		if (!$isSupported($value)) {
			throw new InvalidArgumentException('only strings/string arrays are supported');
		}
	}
}
