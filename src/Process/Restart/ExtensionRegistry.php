<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use PHPStan\Turbo\TurboExtensionSelector;
use function array_key_exists;
use function explode;
use function extension_loaded;
use function function_exists;
use function get_loaded_extensions;
use function getenv;
use function in_array;
use function ini_get;
use function phpversion;
use function sprintf;
use function str_replace;
use function strtolower;

/**
 * The table of extensions PHPStan restarts for, and how each one is dealt with.
 *
 * Adding a future extension is one row. The rows fall into four kinds:
 *
 * - **coverage and debugging hooks** (xdebug, pcov) and **opcode rewriters**
 *   (uopz) install themselves before any PHP code of the process runs, and
 *   cost between 1.5× and 4.5× of an analysis that never asked for them.
 *   None of them can be switched off from inside the process — xdebug.mode,
 *   pcov.enabled and uopz.disable are all read at startup — so restarting is
 *   the only way. All three do have a startup switch, which is why all three
 *   are flag rows rather than strip rows: measured against a run without the
 *   extension at all, disarming it lands inside the ±2 % noise floor, and it
 *   leaves the extension's own functions reflectable so the analysis result
 *   does not change.
 * - **the turbo extension**, the inverse case: it has to be *loaded* from a
 *   binary next to the phar. A pcntl_fork()ed worker inherits the parent's
 *   extensions, so the spawn-time `-d extension=` injection cannot reach it
 *   and dl() cannot load a binary from next to the phar — the main process
 *   has to be re-executed with it before anything else runs.
 * - **OPcache**, another inverse case: usually dormant on CLI, and
 *   activated by the restart in the configuration OpcacheSettings describes.
 *   Forked workers then inherit the parent's warm opcode cache, and spawned
 *   ones get the same configuration from ProcessHelper. Only where fork is
 *   reachable: elsewhere the main process stays as it is, as it did before
 *   this registry existed (on Windows a second OPcache instance of the same
 *   user would attach to the restarted process's named mapping).
 * - **extensions the turbo extension cannot run next to**: Tideways wraps
 *   internal function calls in a way that hands the extension's native code
 *   a corrupted argument, crashing the analysis
 *   (https://github.com/phpstan/phpstan/issues/15407). No startup switch is
 *   known to keep those hooks out — tideways.enable_cli is documented to
 *   stop trace collection only — so it is the one strip row, and only where
 *   turbo runs: without it Tideways is just another unmeasured profiler.
 *
 * Extensions that only arm on demand — excimer, xhprof, memprof,
 * opentelemetry — measured free while merely loaded, and are deliberately
 * absent from every list here.
 */
final class ExtensionRegistry
{

	/**
	 * Profilers reported by diagnose but never neutralized: their idle cost has
	 * not been measured. Every extension of this shape that *has* been measured
	 * (excimer, xhprof, memprof, opentelemetry) turned out to be free while
	 * merely loaded, so the expectation for these is the same — they stay
	 * listed only so that a slow-run report surfaces them. A row moves out of
	 * here in whichever direction the numbers point.
	 */
	private const UNMEASURED_PROFILER_EXTENSIONS = [
		'blackfire',
		'spx',
		'tideways_xhprof',
	];

	/**
	 * Extensions whose idle cost has been measured, kept here so diagnose can
	 * still say they are loaded without implying they cost anything.
	 */
	private const MEASURED_FREE_EXTENSIONS = [
		'excimer',
		'memprof',
		'opentelemetry',
		'xhprof',
	];

	/**
	 * Reasons found in the current process.
	 *
	 * @return list<RestartReason>
	 */
	public static function collectReasons(bool $allowXdebug, ?string $pharPath = null): array
	{
		$iniSettings = [];
		foreach (['xdebug.mode', 'pcov.enabled', 'uopz.disable'] as $name) {
			$value = ini_get($name);
			if ($value === false) {
				continue;
			}

			$iniSettings[$name] = $value;
		}

		// XDEBUG_MODE wins over the ini file, so the effective mode - not the
		// configured one - is what decides. Resolved here rather than in
		// collectReasonsFor(), which stays a pure function of its arguments
		$environmentMode = getenv('XDEBUG_MODE');
		if ($environmentMode !== false && $environmentMode !== '') {
			$iniSettings['xdebug.mode'] = $environmentMode;
		}

		$opcacheIniEntries = self::isForkCapable() ? OpcacheSettings::getIniEntries() : [];

		return self::collectReasonsFor(
			get_loaded_extensions(),
			$iniSettings,
			self::findTurboExtensionToLoad($pharPath),
			$allowXdebug,
			$opcacheIniEntries,
			OpcacheSettings::getCurrentIniValues($opcacheIniEntries),
		);
	}

	/**
	 * @param list<string> $loadedExtensions
	 * @param array<string, string> $iniSettings
	 * @param string|null $turboExtensionPath the turbo binary to load, null when there is none to load or loading it would not pay off
	 * @param list<string> $opcacheIniEntries `name=value` entries of OpcacheSettings::getIniEntries(), empty when OPcache is to be left alone
	 * @param array<string, string|false> $currentOpcacheIniValues ini_get() of each of those names
	 * @return list<RestartReason>
	 */
	public static function collectReasonsFor(
		array $loadedExtensions,
		array $iniSettings,
		?string $turboExtensionPath,
		bool $allowXdebug,
		array $opcacheIniEntries = [],
		array $currentOpcacheIniValues = [],
	): array
	{
		$reasons = [];

		if (!$allowXdebug && in_array('xdebug', $loadedExtensions, true) && self::isXdebugArmed($iniSettings)) {
			$reasons[] = new RestartReason(
				'xdebug',
				sprintf('xdebug.mode=%s', $iniSettings['xdebug.mode'] ?? 'develop'),
				iniSettings: ['xdebug.mode' => 'off'],
				// xdebug reads XDEBUG_MODE in preference to the ini setting, so
				// flagging the ini alone would be silently overridable
				environmentVariables: ['XDEBUG_MODE' => 'off'],
			);
		}

		if (
			in_array('pcov', $loadedExtensions, true)
			&& self::isIniSettingEnabled($iniSettings['pcov.enabled'] ?? '0')
		) {
			$reasons[] = new RestartReason(
				'pcov',
				'pcov.enabled=1',
				iniSettings: ['pcov.enabled' => '0'],
			);
		}

		if (
			in_array('uopz', $loadedExtensions, true)
			&& !self::isIniSettingEnabled($iniSettings['uopz.disable'] ?? '0')
		) {
			$reasons[] = new RestartReason(
				'uopz',
				'uopz.disable=0',
				iniSettings: ['uopz.disable' => '1'],
			);
		}

		if ($turboExtensionPath !== null && !in_array('phpstan_turbo', $loadedExtensions, true)) {
			$reasons[] = new RestartReason(
				'phpstan_turbo',
				sprintf('not loaded, binary available at %s', $turboExtensionPath),
				commandLineArguments: [
					'-d',
					'extension=' . $turboExtensionPath,
					// doubles as the marker telling TurboExtensionSelector that
					// spawned workers need the -d flag passed explicitly:
					// command-line -d flags, unlike php.ini, are not inherited
					'-d',
					TurboExtensionSelector::EXTENSION_PATH_INI . '=' . $turboExtensionPath,
				],
			);
		}

		if (
			in_array('tideways', $loadedExtensions, true)
			&& ($turboExtensionPath !== null || in_array('phpstan_turbo', $loadedExtensions, true))
		) {
			$reasons[] = new RestartReason(
				'tideways',
				'crashes the turbo extension',
				strippedExtensions: ['tideways'],
			);
		}

		if (OpcacheSettings::isRestartNeeded($opcacheIniEntries, $currentOpcacheIniValues)) {
			$opcacheSettings = [];
			foreach ($opcacheIniEntries as $entry) {
				[$name, $value] = explode('=', $entry, 2);
				$opcacheSettings[$name] = $value;
			}

			$reasons[] = new RestartReason(
				'opcache',
				'not configured the way PHPStan runs fastest',
				iniSettings: $opcacheSettings,
			);
		}

		return $reasons;
	}

	/**
	 * Coverage and profiler extensions loaded in the current process, described
	 * for diagnose output.
	 *
	 * @return list<string>
	 */
	public static function describeDetectedExtensions(): array
	{
		$descriptions = [];

		if (extension_loaded('xdebug')) {
			$descriptions[] = sprintf(
				'xdebug %s (xdebug.mode=%s)',
				self::describeVersion('xdebug'),
				self::describeIniSetting('xdebug.mode'),
			);
		}

		if (extension_loaded('pcov')) {
			$descriptions[] = sprintf(
				'pcov %s (pcov.enabled=%s)',
				self::describeVersion('pcov'),
				self::describeIniSetting('pcov.enabled'),
			);
		}

		if (extension_loaded('uopz')) {
			$descriptions[] = sprintf(
				'uopz %s (uopz.disable=%s)',
				self::describeVersion('uopz'),
				self::describeIniSetting('uopz.disable'),
			);
		}

		if (extension_loaded('tideways')) {
			$descriptions[] = sprintf('tideways %s (unloaded wherever the turbo extension runs)', self::describeVersion('tideways'));
		}

		foreach (self::UNMEASURED_PROFILER_EXTENSIONS as $extension) {
			if (!extension_loaded($extension)) {
				continue;
			}

			$descriptions[] = sprintf('%s %s (idle cost not measured)', $extension, self::describeVersion($extension));
		}

		foreach (self::MEASURED_FREE_EXTENSIONS as $extension) {
			if (!extension_loaded($extension)) {
				continue;
			}

			$descriptions[] = sprintf('%s %s (free while idle)', $extension, self::describeVersion($extension));
		}

		return $descriptions;
	}

	/**
	 * Whether xdebug would actually halt at breakpoints and slow the process
	 * down. Also drives the "--xdebug" notes in CommandHelper.
	 */
	public static function isXdebugActive(): bool
	{
		if (!extension_loaded('xdebug')) {
			return false;
		}

		$mode = (string) getenv('XDEBUG_MODE');
		if ($mode === '') {
			$iniMode = ini_get('xdebug.mode');
			if ($iniMode === false) {
				// xdebug older than 3.0 has no modes and is always armed
				return true;
			}

			$mode = $iniMode;
		}

		return self::isXdebugArmed(['xdebug.mode' => $mode]);
	}

	/**
	 * @param array<string, string> $iniSettings the effective settings, with any XDEBUG_MODE override already applied
	 */
	private static function isXdebugArmed(array $iniSettings): bool
	{
		if (!array_key_exists('xdebug.mode', $iniSettings)) {
			return true;
		}

		foreach (explode(',', str_replace(' ', '', $iniSettings['xdebug.mode'])) as $singleMode) {
			if ($singleMode !== '' && $singleMode !== 'off') {
				return true;
			}
		}

		return false;
	}

	private static function isForkCapable(): bool
	{
		return function_exists('pcntl_fork')
			&& function_exists('pcntl_waitpid')
			&& function_exists('pcntl_wifexited')
			&& function_exists('pcntl_wexitstatus')
			&& function_exists('posix_kill');
	}

	/**
	 * The turbo binary the restart should load, null when there is none or
	 * when loading it would not buy anything.
	 */
	public static function findTurboExtensionToLoad(?string $pharPath = null): ?string
	{
		if (!self::isForkCapable()) {
			// fork mode is impossible here (see ForkParallelChecker) and spawned
			// workers get the extension from ProcessHelper already
			return null;
		}

		return TurboExtensionSelector::findExtension($pharPath);
	}

	private static function isIniSettingEnabled(string $value): bool
	{
		// bool ini settings normalize to "" or "1", but a hand-written php.ini
		// value reaches ini_get() the way it was written
		return !in_array(strtolower($value), ['', '0', 'off', 'false', 'no'], true);
	}

	private static function describeIniSetting(string $name): string
	{
		$value = ini_get($name);

		return $value === false || $value === '' ? 'unset' : $value;
	}

	private static function describeVersion(string $extension): string
	{
		$version = phpversion($extension);

		return $version === false ? 'unknown version' : $version;
	}

}
