<?php declare(strict_types = 1);

namespace Bug9384b;

use function PHPStan\Testing\assertType;

class BinaryOr
{
	private const TYPE_NONE               = 0;
	private const TYPE_TRACK_DEPRECATIONS = 1;
	private const TYPE_TRIGGER_ERROR      = 2;

	/** @var int-mask-of<self::TYPE_*>|null */
	private static $type;

	public static function enableTrackingDeprecations(): void
	{
		assertType('int<0, 3>|null', self::$type);
		assertType('1', self::TYPE_TRACK_DEPRECATIONS);
		assertType('int<0, 3>', self::$type | self::TYPE_TRACK_DEPRECATIONS);
	}
}
