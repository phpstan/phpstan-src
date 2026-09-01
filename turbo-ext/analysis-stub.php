<?php declare(strict_types = 1);

/**
 * Symbol stub for PHPStan's self-analysis (registered via scanFiles in
 * build/phpstan.neon). Never executed or autoloaded. Only symbols referenced
 * from analysed code need to appear here; the generated runtime stubs
 * (vendor/turbo-stubs.php) are not part of the analysed paths.
 */

namespace PHPStanTurbo;

final class Runtime
{

	/**
	 * @param array<string, class-string> $classMap
	 */
	public static function configure(array $classMap): void
	{
	}

	public static function enablePharForkGuard(string $pharPath): void
	{
	}

	public static function trustTypesUnder(string $prefix): bool
	{
	}

	public static function exitImmediately(): never
	{
	}

}
