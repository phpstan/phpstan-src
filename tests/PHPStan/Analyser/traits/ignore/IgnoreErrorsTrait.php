<?php declare(strict_types = 1);

namespace TraitsIgnoreErrors;

use Countable;
use RuntimeException;
use function is_int;
use function is_subclass_of;

trait IgnoreErrorsTrait
{

	public static function check(): void
	{
		/* @phpstan-ignore function.alreadyNarrowedType */
		if (!is_subclass_of(self::class, Countable::class)) {
			throw new RuntimeException('not countable');
		}
	}

	public static function checkImpossible(): bool
	{
		/** @phpstan-ignore function.impossibleType */
		return is_int(self::class);
	}

	public static function compare(): bool
	{
		/** @phpstan-ignore identical.alwaysFalse */
		return self::class === 'TraitsIgnoreErrors\Nonexistent';
	}

	public static function compareNextLine(): bool
	{
		/** @phpstan-ignore-next-line */
		return self::class === 'TraitsIgnoreErrors\AlsoNonexistent';
	}

	public static function compareSameLine(): bool
	{
		return self::class === 'TraitsIgnoreErrors\YetAnotherNonexistent'; // @phpstan-ignore-line
	}

	public static function compareTrailingIdentifier(): bool
	{
		return self::class === 'TraitsIgnoreErrors\LastNonexistent'; // @phpstan-ignore identical.alwaysFalse
	}

}
