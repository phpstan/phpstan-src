<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @phpstan-type Level = self::DETECT|self::PREVENT|null
 */
#[ReferencedByTurboExtension(key: 'reportUnsafeArrayStringKeyCastingToggle')]
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
	}

}
