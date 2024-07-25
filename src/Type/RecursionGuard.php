<?php declare(strict_types = 1);

namespace PHPStan\Type;

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

}
