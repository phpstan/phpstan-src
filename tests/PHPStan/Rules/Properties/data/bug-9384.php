<?php declare(strict_types = 1);

namespace Bug9384;

class Deprecation
{
	private const TYPE_NONE               = 0;
	private const TYPE_TRACK_DEPRECATIONS = 1;
	private const TYPE_TRIGGER_ERROR      = 2;

	/** @var int-mask-of<self::TYPE_*> */
	private static $type = 0;

	public static function enableTrackingDeprecations(): void
	{
		self::$type |= self::TYPE_TRACK_DEPRECATIONS;
	}

	public static function invalidValue(): void
	{
		self::$type = self::$type | 10; // invalid value
	}

	public static function enableWithTriggerError(): void
	{
		self::$type |= self::TYPE_TRIGGER_ERROR;
	}

	public static function disable(): void
	{
		self::$type = self::TYPE_NONE;
	}
}
