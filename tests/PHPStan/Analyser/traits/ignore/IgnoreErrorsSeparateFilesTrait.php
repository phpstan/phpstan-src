<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsSeparate;

use Countable;
use RuntimeException;
use function is_subclass_of;

trait IgnoreErrorsSeparateFilesTrait
{

	public static function check(): void
	{
		/* @phpstan-ignore function.alreadyNarrowedType */
		if (!is_subclass_of(self::class, Countable::class)) {
			throw new RuntimeException('not countable');
		}
	}

	public static function compare(): bool
	{
		/** @phpstan-ignore identical.alwaysFalse */
		return self::class === 'TraitsIgnoreErrorsSeparate\Nonexistent';
	}

}
