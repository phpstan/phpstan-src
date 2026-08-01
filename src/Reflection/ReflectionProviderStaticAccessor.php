<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\TypeCombinator;

final class ReflectionProviderStaticAccessor
{

	private static ?ReflectionProvider $instance = null;

	private function __construct()
	{
	}

	public static function registerInstance(ReflectionProvider $reflectionProvider): void
	{
		self::$instance = $reflectionProvider;

		// Type operations read this accessor, so a memoized result is only valid
		// for the provider it was computed under. Dropping the memo here means no
		// caller can swap the provider - not even temporarily - and leak types
		// resolved against the old one into the next.
		TypeCombinator::clearCache();
	}

	public static function getInstance(): ReflectionProvider
	{
		if (self::$instance === null) {
			throw new MissingStaticAccessorInstanceException();
		}
		return self::$instance;
	}

}
