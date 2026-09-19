<?php declare(strict_types = 1);

namespace PHPStan\Process;

use function get_cfg_var;
use function ini_get;
use function is_string;
use function php_ini_loaded_file;
use function php_ini_scanned_files;
use function sys_get_temp_dir;
use function trim;

/**
 * The PHP command-line options a child PHP process needs so that it runs with
 * the PHP configuration of the process starting it - the spawned workers of
 * ProcessHelper, the re-executed main process of TurboProcessRestarter, and
 * the PHPStan Pro process of FixerApplication.
 *
 * The environment carries over to a child on its own, so PHPRC and
 * PHP_INI_SCAN_DIR - what composer/xdebug-handler sets up for a persistent
 * restart - need nothing from here. The command line carries over to nothing,
 * and repeating just `-c`, as the worker command used to, reproduces the main
 * php.ini and nothing else:
 *
 * - The ini scan directory is read again. That matches the spawning process
 *   when it read it too, and contradicts it when it did not - `php -n`, or a
 *   `-c` pointing elsewhere, then gives the worker every extension and setting
 *   the main process was deliberately started without.
 * - `-d` entries are dropped. `php -d xdebug.mode=off vendor/bin/phpstan` turns
 *   Xdebug off for the main process alone: xdebug-handler sees an inactive
 *   Xdebug and rightly does not restart, so no PHPRC is set up either, and every
 *   worker loads Xdebug again from the scan directory in whatever mode the ini
 *   says - the analysis runs under an active Xdebug, several times slower, with
 *   nothing on screen saying so
 *   (https://github.com/phpstan/phpstan/issues/15189).
 *
 * PHP does not record which directives came from the command line, so `-d`
 * entries cannot be repeated as a group - only the two that matter to a PHPStan
 * process are: xdebug.mode, whose value decides how fast the whole run is, and
 * sys_temp_dir, which decides where the result cache lives.
 */
final class InheritedPhpConfig
{

	/**
	 * @return list<string>
	 */
	public static function getArgs(): array
	{
		return self::resolveArgs(php_ini_loaded_file(), php_ini_scanned_files(), sys_get_temp_dir(), self::getXdebugMode());
	}

	/**
	 * The xdebug.mode in effect, or false when nothing set it.
	 *
	 * ini_get() answers only for a loaded Xdebug - for any other PHP the
	 * directive is not registered and only the raw ini entry exists, which is
	 * what a child loading Xdebug when we do not would be configured by.
	 */
	private static function getXdebugMode(): string|false
	{
		$mode = ini_get('xdebug.mode');
		if ($mode !== false) {
			return $mode;
		}

		$mode = get_cfg_var('xdebug.mode');

		return is_string($mode) ? $mode : false;
	}

	/**
	 * @param string|false $loadedIniFile php_ini_loaded_file() of the spawning process
	 * @param string|false $scannedIniFiles php_ini_scanned_files() of the spawning process
	 * @param string $tempDir sys_get_temp_dir() of the spawning process
	 * @param string|false $xdebugMode see getXdebugMode()
	 * @return list<string>
	 */
	public static function resolveArgs(string|false $loadedIniFile, string|false $scannedIniFiles, string $tempDir, string|false $xdebugMode): array
	{
		$args = [];
		if ($scannedIniFiles === false || trim($scannedIniFiles) === '') {
			// -n only suppresses the scan directory here: an explicit -c is
			// still honored next to it, the way xdebug-handler restarts
			$args[] = '-n';
		}
		if ($loadedIniFile !== false && $loadedIniFile !== '') {
			$args[] = '-c';
			$args[] = $loadedIniFile;
		}
		$args[] = '-d';
		// quote value so PHP will parse it as a string when the path contains a bitwise operator like ~
		$args[] = "sys_temp_dir='" . $tempDir . "'";
		if ($xdebugMode !== false) {
			$args[] = '-d';
			$args[] = 'xdebug.mode=' . $xdebugMode;
		}

		return $args;
	}

}
