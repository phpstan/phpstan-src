<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait CheckedTrait
{

	public static function check(): void
	{
		// @phpstan-ignore function.alreadyNarrowedType (legacy)
		if (!is_subclass_of(self::class, Checked::class)) {
			throw new \RuntimeException('not checked');
		}
	}

}
