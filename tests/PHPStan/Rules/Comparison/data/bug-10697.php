<?php declare(strict_types=1);

namespace Bug10697;

/** @template T */
abstract class HelloWorld
{
	public static function foo(): void
	{
		if (self::class === static::class) {}
	}
}
