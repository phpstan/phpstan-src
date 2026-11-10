<?php declare(strict_types = 1);

namespace Bug14993;

trait CheckedTrait
{
	public static function check(): void
	{
		/* @phpstan-ignore function.alreadyNarrowedType */
		if (!is_subclass_of(self::class, \Countable::class)) {
			throw new \RuntimeException('not countable');
		}
	}
}
