<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\TypeCombinator;

final class PhpVersionStaticAccessor
{

	private static ?PhpVersion $instance = null;

	private function __construct()
	{
	}

	public static function registerInstance(PhpVersion $phpVersion): void
	{
		self::$instance = $phpVersion;

		// Type operations read this accessor, so a memoized result is only valid
		// for the PHP version it was computed under.
		TypeCombinator::clearCache();
	}

	public static function getInstance(): PhpVersion
	{
		if (self::$instance === null) {
			throw new MissingStaticAccessorInstanceException();
		}
		return self::$instance;
	}

}
