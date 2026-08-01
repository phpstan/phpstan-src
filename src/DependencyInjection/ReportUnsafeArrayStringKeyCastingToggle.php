<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Type\TypeCombinator;

/**
 * @phpstan-type Level = self::DETECT|self::PREVENT|null
 */
final class ReportUnsafeArrayStringKeyCastingToggle
{

	public const DETECT = 'detect';

	public const PREVENT = 'prevent';

	/** @var Level */
	private static ?string $level = null;

	/**
	 * @return Level
	 */
	public static function getLevel(): ?string
	{
		return self::$level;
	}

	/**
	 * @param Level $level
	 */
	public static function setLevel(?string $level): void
	{
		self::$level = $level;

		// Type operations read this toggle, so a memoized result is only valid
		// for the level it was computed under.
		TypeCombinator::clearCache();
	}

}
