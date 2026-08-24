<?php declare(strict_types = 1);

namespace PHPStan\Process;

use function extension_loaded;
use function getenv;
use function ini_get;
use function phpversion;

/**
 * pcov hooks into every userland function call as soon as the extension starts up,
 * whether or not any coverage is being collected. Nothing in PHPStan collects
 * coverage, so on a machine that has pcov installed - typically a CI image built
 * for a test suite - the hook is pure overhead. It makes an analysis dramatically
 * slower: function calls in the analysed process become several times more
 * expensive, which measures as roughly 40 % of PHPStan's wall clock time.
 *
 * The hook is installed before any PHP code of the process runs and pcov.enabled is
 * PHP_INI_SYSTEM, so a process cannot get rid of it with ini_set(). The only way is
 * to start the process with pcov.enabled=0, which is what PHPStan does for its
 * worker processes (see ProcessHelper).
 */
final class PcovHelper
{

	public const DISABLED_INI_SETTING = 'pcov.enabled=0';

	public const ALLOW_ENV_VARIABLE = 'PHPSTAN_ALLOW_PCOV';

	public static function isLoaded(): bool
	{
		return extension_loaded('pcov');
	}

	/** Whether pcov's call hook is installed in the current process. */
	public static function isActive(): bool
	{
		if (!self::isLoaded()) {
			return false;
		}

		$enabled = ini_get('pcov.enabled');

		return $enabled !== false && $enabled !== '' && $enabled !== '0';
	}

	/** Whether the user asked PHPStan to leave pcov alone. */
	public static function isAllowed(): bool
	{
		return getenv(self::ALLOW_ENV_VARIABLE) === '1';
	}

	public static function shouldDisableInSubProcesses(): bool
	{
		return self::isLoaded() && !self::isAllowed();
	}

	public static function getVersion(): ?string
	{
		$version = phpversion('pcov');

		return $version === false ? null : $version;
	}

}
