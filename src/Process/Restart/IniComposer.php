<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use function addcslashes;
use function array_key_exists;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function ini_get_all;
use function is_string;
use function parse_ini_string;
use function php_ini_loaded_file;
use function php_ini_scanned_files;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;
use const PHP_EOL;
use const PREG_OFFSET_CAPTURE;

/**
 * Builds the php.ini the restarted process runs with.
 *
 * It is a concatenation of every ini file the current process loaded, with the
 * `extension=`/`zend_extension=` lines of the stripped extensions commented
 * out, followed by the runtime `ini_get_all()` diff, followed by the forced
 * settings. The runtime diff is what makes argv reconstruction unnecessary:
 * a `php -d memory_limit=2G bin/phpstan` survives the restart even though
 * $argv contains no `-d` flags, and so does every other command-line
 * override.
 *
 * Unlike composer/xdebug-handler, whose tmp ini this is otherwise modelled
 * on, nothing about opcache is decided here — its settings pass through
 * verbatim, unless the opcache row of ExtensionRegistry forces them like any
 * other reason's settings.
 */
final class IniComposer
{

	/**
	 * Writes the composed ini for the current process.
	 *
	 * @param list<string> $strippedExtensions
	 * @param array<string, string> $iniSettings
	 * @return string|null the path of the composed ini, null when it could not be built
	 */
	public static function compose(array $strippedExtensions, array $iniSettings): ?string
	{
		$runtimeSettings = ini_get_all(details: false);
		if ($runtimeSettings === false) {
			return null;
		}

		$content = self::composeContent(
			self::getAllIniFiles(),
			$strippedExtensions,
			$iniSettings,
			$runtimeSettings,
		);
		if ($content === null) {
			return null;
		}

		$file = @tempnam(sys_get_temp_dir(), 'phpstan-restart-ini-');
		if ($file === false) {
			return null;
		}

		if (@file_put_contents($file, $content) === false) {
			@unlink($file);
			return null;
		}

		return $file;
	}

	/**
	 * @param list<string> $iniFiles
	 * @param list<string> $strippedExtensions
	 * @param array<string, string> $iniSettings
	 * @param array<string, mixed> $runtimeSettings as returned by ini_get_all(null, false)
	 * @return string|null null when one of the ini files could not be read or parsed
	 */
	public static function composeContent(
		array $iniFiles,
		array $strippedExtensions,
		array $iniSettings,
		array $runtimeSettings,
	): ?string
	{
		$content = '';
		foreach ($iniFiles as $file) {
			$data = @file_get_contents($file);
			if ($data === false) {
				return null;
			}

			$content .= self::stripExtensionLines(self::cutAtFirstSection($data), $strippedExtensions) . PHP_EOL;
		}

		$parsed = @parse_ini_string($content);
		if ($parsed === false) {
			return null;
		}

		foreach ($runtimeSettings as $name => $value) {
			if (!is_string($value)) {
				continue;
			}
			if ($name === 'apc.mmap_file_mask') {
				// cannot be set from an ini file, see composer/xdebug-handler
				continue;
			}
			if (self::belongsToExtension($name, $strippedExtensions)) {
				continue;
			}
			if (array_key_exists($name, $parsed) && $parsed[$name] === $value) {
				continue;
			}

			$content .= $name . '="' . addcslashes($value, '\\"') . '"' . PHP_EOL;
		}

		// last, so they win over the user's files and over the runtime diff
		foreach ($iniSettings as $name => $value) {
			$content .= $name . '="' . addcslashes($value, '\\"') . '"' . PHP_EOL;
		}

		return $content;
	}

	/**
	 * Every ini file the current process loaded, in load order.
	 *
	 * @return list<string>
	 */
	public static function getAllIniFiles(): array
	{
		$files = [];

		$loadedFile = php_ini_loaded_file();
		if ($loadedFile !== false) {
			$files[] = $loadedFile;
		}

		$scannedFiles = php_ini_scanned_files();
		if ($scannedFiles !== false) {
			foreach (explode(',', $scannedFiles) as $scannedFile) {
				$scannedFile = trim($scannedFile);
				if ($scannedFile === '') {
					continue;
				}

				$files[] = $scannedFile;
			}
		}

		return $files;
	}

	/**
	 * Directives after a [PATH=] or [HOST=] section only apply to that path or
	 * host, so copying them into an unsectioned file would apply them to
	 * everything.
	 */
	private static function cutAtFirstSection(string $data): string
	{
		if (preg_match('/^\s*\[(?:PATH|HOST)\s*=/mi', $data, $matches, PREG_OFFSET_CAPTURE) !== 1) {
			return $data;
		}

		return substr($data, 0, $matches[0][1]);
	}

	/**
	 * @param list<string> $strippedExtensions
	 */
	private static function stripExtensionLines(string $data, array $strippedExtensions): string
	{
		foreach ($strippedExtensions as $extension) {
			$replaced = preg_replace(
				// not followed by a name character, so stripping tideways leaves tideways_xhprof loaded
				'/^\s*((?:zend_)?extension\s*=.*' . preg_quote($extension, '/') . '(?![\w-]).*)$/mi',
				';$1',
				$data,
			);
			if ($replaced === null) {
				continue;
			}

			$data = $replaced;
		}

		return $data;
	}

	/**
	 * A stripped extension's own settings have no owner in the restarted
	 * process; carrying them over would only make php complain.
	 *
	 * @param list<string> $strippedExtensions
	 */
	private static function belongsToExtension(string $settingName, array $strippedExtensions): bool
	{
		foreach ($strippedExtensions as $extension) {
			if ($settingName === $extension || substr($settingName, 0, strlen($extension) + 1) === $extension . '.') {
				return true;
			}
		}

		return false;
	}

}
