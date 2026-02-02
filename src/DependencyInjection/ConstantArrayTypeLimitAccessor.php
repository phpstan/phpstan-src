<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

final class ConstantArrayTypeLimitAccessor
{

	private static int $limit = 256;

	public static function getLimit(): int
	{
		return self::$limit;
	}

	public static function setLimit(int $limit): void
	{
		self::$limit = $limit;
	}

}
