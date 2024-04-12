<?php declare(strict_types=1);

namespace BugInstanceofStaticVsThisImpossibleCheck;

interface FooInterface
{
	/** @phpstan-assert-if-true null $v */
	public static function isNull(?int $v): bool;
}

class FooBase
{
	/** @param int $v */
	public function bar($v): void
	{
		if ($this instanceof FooInterface) {
			if ($this->isNull($v)) echo 'a';
			if ($this::isNull($v)) echo 'a';
			if (static::isNull($v)) echo 'a';
		}

		if (is_a(static::class, FooInterface::class, true)) {
			if ($this->isNull($v)) echo 'a';
			if ($this::isNull($v)) echo 'a';
			if (static::isNull($v)) echo 'a';
		}
	}
}
