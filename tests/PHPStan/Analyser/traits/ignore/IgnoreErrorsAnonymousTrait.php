<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsAnonymous;

use Countable;
use RuntimeException;
use function is_subclass_of;

trait IgnoreErrorsAnonymousTrait
{

	public function check(): void
	{
		/* @phpstan-ignore function.alreadyNarrowedType */
		if (!is_subclass_of(static::class, Countable::class)) {
			throw new RuntimeException('not countable');
		}
	}

}
