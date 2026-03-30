<?php declare(strict_types=1);

namespace Bug12062;

abstract class Base
{
	public static function get(mixed $param): mixed
	{
		static::validateParam($param);
		return $param;
	}

	public static function set(mixed $param, mixed $value): void
	{
		static::validateParam($param);
	}

	public static function clear(mixed $param): void
	{
		static::validateParam($param);
	}

	protected static function validateParam(mixed $param): bool
	{
		return true;
	}
}

abstract class IntParam extends Base
{
	protected static function validateParam(mixed $param): bool
	{
		if (!is_int($param)) {
			throw new \InvalidArgumentException('Must be int');
		}
		return true;
	}
}
