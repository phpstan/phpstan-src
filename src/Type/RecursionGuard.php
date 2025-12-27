<?php declare(strict_types = 1);

namespace PHPStan\Type;

use function spl_object_id;

final class RecursionGuard
{

	/** @var true[] */
	private static array $context = [];

	/**
	 * @template T
	 * @param callable(): T $callback
	 * @return T|ErrorType
	 */
	public static function run(Type $type, callable $callback)
	{
		$key = $type->describe(VerbosityLevel::value());
		if (isset(self::$context[$key])) {
			return new ErrorType();
		}

		try {
			self::$context[$key] = true;
			return $callback();
		} finally {
			unset(self::$context[$key]);
		}
	}

	/**
	 * @template T
	 * @param callable(): T $callback
	 * @return T|ErrorType
	 */
	public static function runOnObjectIdentity(Type $type, callable $callback)
	{
		$key = spl_object_id($type);
		if (isset(self::$context[$key])) {
			return new ErrorType();
		}

		try {
			self::$context[$key] = true;
			return $callback();
		} finally {
			unset(self::$context[$key]);
		}
	}

}
