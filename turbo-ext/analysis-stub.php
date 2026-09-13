<?php declare(strict_types = 1);

/**
 * Symbol stub for PHPStan's self-analysis (registered via scanFiles in
 * build/phpstan.neon). Never executed or autoloaded. Only symbols referenced
 * from analysed code need to appear here.
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

	/**
	 * @param array<string, string> $twinFiles
	 */
	public static function activateShadowing(array $twinFiles, ?string $prefix = null): void
	{
	}

	public static function isShadowing(): bool
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
