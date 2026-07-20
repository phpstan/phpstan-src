<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function spl_object_id;

#[ReferencedByTurboExtension(key: 'recursionGuard')]
final class RecursionGuard
{

	/**
	 * While this is non-empty, run() and runOnObjectIdentity() short-circuit to ErrorType,
	 * so a type operation's result depends on the call stack and not only on its arguments.
	 * The native extension reads this property to know when it must not memoize
	 * TypeCombinator's operations (see PHPStanTurbo\TypeCombinatorCache).
	 *
	 * @var true[]
	 */
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
