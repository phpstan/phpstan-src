<?php declare(strict_types=1);

namespace CallPrivateMethodStaticInstanceof;

interface FooInterface
{
	public static function fooPrivate(): int;
}

class FooBase
{
	private static function fooPrivate(): string
	{
		return 'a';
	}

	public function bar(): void
	{
		if ($this instanceof FooInterface) {
			static::fooPrivate();
		}

		if (is_a(static::class, FooInterface::class, true)) {
			static::fooPrivate();
		}

		static::fooPrivate();
	}
}
