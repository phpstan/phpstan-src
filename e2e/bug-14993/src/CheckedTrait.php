<?php

namespace Bug14993;

use Countable;
use RuntimeException;

trait CheckedTrait
{

	public static function check(): void
	{
		if (!is_subclass_of(self::class, Countable::class)) { // @phpstan-ignore function.alreadyNarrowedType
			throw new RuntimeException('not countable');
		}
	}

}
