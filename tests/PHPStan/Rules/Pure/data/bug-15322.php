<?php

namespace Bug15322;

/**
 * @phpstan-all-methods-pure
 * @method int magic()
 */
class MagicMethods
{
}

/**
 * @phpstan-all-methods-pure
 * @method int magic()
 */
class ImpureDispatcher
{
	/** @phpstan-impure */
	public function __call(string $name, array $arguments): int
	{
		return time();
	}
}

/**
 * @phpstan-all-methods-pure
 * @method static int magic()
 */
class ImpureStaticDispatcher
{
	/** @phpstan-impure */
	public static function __callStatic(string $name, array $arguments): int
	{
		return time();
	}
}

class Test
{
	/** @phpstan-pure */
	public function run(MagicMethods $object): int
	{
		return $object->magic();
	}

	/** @phpstan-pure */
	public function runImpureDispatcher(ImpureDispatcher $object): int
	{
		return $object->magic();
	}

	/** @phpstan-pure */
	public function runImpureStaticDispatcher(): int
	{
		return ImpureStaticDispatcher::magic();
	}
}
