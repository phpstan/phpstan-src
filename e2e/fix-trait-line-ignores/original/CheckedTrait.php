<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait CheckedTrait
{

	public static function check(): void
	{
		// @phpstan-ignore-next-line legacy
		if (!is_subclass_of(self::class, Checked::class)) {
			throw new \RuntimeException('not checked');
		}
	}

}
