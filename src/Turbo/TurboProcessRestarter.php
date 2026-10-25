<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Process\Restart\RestartManager;

/**
 * Kept for programs that load PHPStan's phar and restart into PHPStan Turbo
 * themselves, Rector for example. PHPStan's own bin/phpstan calls
 * RestartManager directly.
 */
final class TurboProcessRestarter
{

	/**
	 * On success the call never returns — the process image is replaced.
	 *
	 * Another program that loads PHPStan's phar, Rector for example, passes
	 * the path to that phar: Phar::running() only knows the phar that runs.
	 *
	 * Without $appName only what the turbo extension needs is restarted for,
	 * as before. With it, the same single restart also neutralizes xdebug,
	 * pcov and the other hooks PHPStan's own restart does, unless --xdebug is
	 * in $argv or <APP>_ALLOW_XDEBUG is set - the variable composer/xdebug-handler
	 * reads for that application, so a caller can drop its own xdebug restart.
	 *
	 * With $withOpcache false, the restart does not turn on OPcache. A run
	 * that mostly reads the result cache is then faster, and a full analysis
	 * uses a little more CPU.
	 *
	 * @api
	 * @param list<string> $argv
	 */
	public static function restartIfSuitable(array $argv, ?string $pharPath = null, ?string $appName = null, bool $withOpcache = true): void
	{
		if (!$withOpcache) {
			$extensions = $appName === null
				? [
					'phpstan_turbo',
					'tideways',
				]
				: [
					'xdebug',
					'pcov',
					'uopz',
					'phpstan_turbo',
					'tideways',
				];
			RestartManager::run($argv, $pharPath, $extensions, $appName ?? 'phpstan');
			return;
		}

		if ($appName === null) {
			RestartManager::run($argv, $pharPath, ['phpstan_turbo', 'tideways', 'opcache']);
			return;
		}

		RestartManager::run($argv, $pharPath, appName: $appName);
	}

}
