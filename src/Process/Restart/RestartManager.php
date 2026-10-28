<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use function array_merge;
use function array_reverse;
use function array_slice;
use function constant;
use function defined;
use function explode;
use function fclose;
use function function_exists;
use function fwrite;
use function get_cfg_var;
use function getenv;
use function getmypid;
use function implode;
use function in_array;
use function ini_get;
use function pcntl_fork;
use function pcntl_signal;
use function php_ini_loaded_file;
use function posix_getppid;
use function posix_kill;
use function putenv;
use function register_shutdown_function;
use function sprintf;
use function strpos;
use function strtoupper;
use function unlink;
use function usleep;
use const PHP_EOL;
use const PHP_SAPI;
use const SIG_IGN;
use const SIGKILL;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * Re-executes the PHPStan process once, with everything that would slow the
 * analysis down neutralized and everything that would speed it up loaded.
 *
 * Called from bin/phpstan before the Composer autoloader, because an extension
 * can only be loaded or unloaded by starting a process, and because the
 * cheapest restart is the one that happens before any work is done. The
 * namespace is deliberately self-contained for that reason: no autoloading, no
 * dependencies beyond PHPStan\Turbo.
 *
 * Running before the autoloader also means running before the symfony
 * polyfills are registered, on every PHP the phar supports — down to 7.4. The
 * files bin/phpstan loads first may only call functions native to PHP 7.4, or
 * guard the newer ones with function_exists().
 *
 * There is exactly one restart. Reasons are collected from
 * ExtensionRegistry, their neutralizations merged into one composed php.ini
 * (IniComposer) plus one command line, and the result exec'd
 * (RestartExecutor). The composed ini is exported through PHPRC, so spawned
 * workers inherit it and forked ones inherit it in memory. A marker
 * environment variable naming the reasons is set on the restarted command; its
 * presence stops a second restart, so an extension that is somehow still armed
 * afterwards degrades to a diagnose note rather than to a boot loop.
 *
 * Only -vvv (or composer/xdebug-handler's XDEBUG_HANDLER_DEBUG) prints the
 * restart line. After the restart there is nothing left to warn about, and the
 * output is noisy enough as it is — `diagnose` carries the full story.
 */
final class RestartManager
{

	/**
	 * Names the reasons the running process was restarted for, as a CSV. Its
	 * presence means "do not restart again".
	 */
	public const REASONS_ENV_VARIABLE = 'PHPSTAN_RESTART';

	/**
	 * Set by ProcessHelper on the workers it spawns: their configuration is
	 * decided by the process spawning them, so they never restart themselves.
	 */
	public const RESTARTED_INI = 'phpstan.restarted';

	/**
	 * Points the restarted process at the composed ini it has to delete when it
	 * is done.
	 */
	private const TEMPORARY_INI_ENV_VARIABLE = 'PHPSTAN_RESTART_INI';

	/**
	 * Appended to the upper-cased application name, PHPSTAN_ALLOW_XDEBUG for
	 * PHPStan: a deprecated alias of --xdebug. composer/xdebug-handler derived
	 * it from the prefix it was constructed with, and CI configurations set it.
	 */
	private const ALLOW_XDEBUG_ENV_VARIABLE_SUFFIX = '_ALLOW_XDEBUG';

	/**
	 * composer/xdebug-handler's switch for reporting its restart; it prints the
	 * restart line too, so that CI configurations relying on it keep working.
	 */
	private const XDEBUG_HANDLER_DEBUG_ENV_VARIABLE = 'XDEBUG_HANDLER_DEBUG';

	/** How often the watchdog checks whether the restarted process is still alive */
	private const WATCHDOG_POLL_INTERVAL_MICROSECONDS = 100_000;

	/**
	 * Commands that must run in the process they were started in. Workers are
	 * already clean through PHPRC inheritance; diagnose reports what a restart
	 * would do instead of doing it; the rest analyse nothing, so re-executing
	 * them would buy an exec and no speed.
	 */
	private const NON_RESTARTING_COMMANDS = [
		'worker',
		'fixer:worker',
		'diagnose',
		'clear-result-cache',
		'dump-parameters',
	];

	/**
	 * On success this never returns — the process is replaced by, or waits for,
	 * the restarted one.
	 *
	 * @param list<string> $argv
	 * @param string|null $pharPath the phar to look for the turbo binary next to, see TurboProcessRestarter
	 * @param list<string>|null $extensions restart only for these reasons, null for all of them
	 * @param string $appName prefix of the <APP>_ALLOW_XDEBUG environment variable, see TurboProcessRestarter
	 */
	public static function run(array $argv, ?string $pharPath, ?array $extensions, string $appName): void
	{
		self::cleanUpComposedIni();

		$reasons = [];
		foreach (self::collectReasons($argv, $pharPath, $appName) as $reason) {
			if ($extensions !== null && !in_array($reason->getExtension(), $extensions, true)) {
				continue;
			}

			$reasons[] = $reason;
		}
		if ($reasons === []) {
			return;
		}

		if (!RestartExecutor::isSupported()) {
			// continue armed, silently; diagnose explains
			return;
		}

		self::restart($argv, $reasons);
	}

	/**
	 * The reasons this process should restart for. Empty when it must not
	 * restart at all — see isEligible().
	 *
	 * @param list<string> $argv
	 * @return list<RestartReason>
	 */
	public static function collectReasons(array $argv, ?string $pharPath, string $appName): array
	{
		if (!self::isEligible($argv)) {
			return [];
		}

		return RestartExtensionRegistry::collectReasons(self::isXdebugAllowed($argv, $appName), $pharPath);
	}

	/**
	 * Whether the command in $argv may restart at all, regardless of what is
	 * loaded.
	 *
	 * @param list<string> $argv
	 */
	public static function isEligible(array $argv): bool
	{
		if (PHP_SAPI !== 'cli') {
			return false;
		}

		if (self::wasRestarted()) {
			return false;
		}

		if (isset($_SERVER['BLACKFIRE_AGENT_SOCKET'])) {
			// the probe cannot follow pcntl_exec() - the profile is truncated or
			// PHP segfaults - and a passthrough restart would only profile the
			// parent waiting for its child; see
			// https://support.blackfire.platform.sh/hc/en-us/articles/4843014509202-Conflicts-with-pcntl-exec-calls
			return false;
		}

		$commandName = self::findCommandName($argv);

		return $commandName === null || !in_array($commandName, self::NON_RESTARTING_COMMANDS, true);
	}

	public static function wasRestarted(): bool
	{
		return getenv(self::REASONS_ENV_VARIABLE) !== false || get_cfg_var(self::RESTARTED_INI) !== false;
	}

	/**
	 * The extensions the running process was restarted for, empty when it was
	 * not restarted.
	 *
	 * @return list<string>
	 */
	public static function getPerformedRestartReasons(): array
	{
		$reasons = (string) getenv(self::REASONS_ENV_VARIABLE);
		if ($reasons === '') {
			return [];
		}

		return explode(',', $reasons);
	}

	public static function wasRestartedFor(string $extension): bool
	{
		return in_array($extension, self::getPerformedRestartReasons(), true);
	}

	/**
	 * @param list<string> $argv
	 * @param non-empty-list<RestartReason> $reasons
	 */
	private static function restart(array $argv, array $reasons): void
	{
		$strippedExtensions = [];
		$iniSettings = [];
		$environmentVariables = [];
		$phpArguments = [];
		$reasonNames = [];

		foreach ($reasons as $reason) {
			$reasonNames[] = $reason->getExtension();
			foreach ($reason->getStrippedExtensions() as $extension) {
				$strippedExtensions[] = $extension;
			}
			foreach ($reason->getIniSettings() as $name => $value) {
				$iniSettings[$name] = $value;
			}
			foreach ($reason->getEnvironmentVariables() as $name => $value) {
				$environmentVariables[$name] = $value;
			}
			foreach ($reason->getCommandLineArguments() as $argument) {
				$phpArguments[] = $argument;
			}
		}

		$temporaryIni = null;
		$rollbacks = [self::exportEnvironment($environmentVariables)];
		$restoreEnvironment = static function () use (&$rollbacks): void {
			foreach (array_reverse($rollbacks) as $rollback) {
				$rollback();
			}
		};

		if ($strippedExtensions !== [] || $iniSettings !== []) {
			$temporaryIni = IniComposer::compose($strippedExtensions, $iniSettings);
			if ($temporaryIni === null) {
				$restoreEnvironment();
				return;
			}

			$restore = self::propagateComposedIni($temporaryIni);
			if ($restore === null) {
				$restoreEnvironment();
				@unlink($temporaryIni);
				return;
			}

			$rollbacks[] = $restore;
		} else {
			// no ini had to be composed, but $argv contains neither the -c nor
			// the -d flags this process was started with — carry over the ones
			// that matter by hand
			$phpArguments = array_merge(self::getPassedThroughArguments(), $phpArguments);
		}

		if (!putenv(self::REASONS_ENV_VARIABLE . '=' . implode(',', $reasonNames))) {
			$restoreEnvironment();
			self::deleteComposedIni($temporaryIni);
			return;
		}

		if (self::isRestartReported($argv)) {
			self::writeRestartLine($reasons);
		}

		RestartExecutor::execute(array_merge($phpArguments, $argv), $temporaryIni);

		// the restart could not be performed after all — run on as this process
		// is, without leaving the composed ini behind for this run's workers
		putenv(self::REASONS_ENV_VARIABLE);
		$restoreEnvironment();
		self::deleteComposedIni($temporaryIni);
	}

	/**
	 * Exports the arming switches an ini file cannot express — xdebug reads
	 * XDEBUG_MODE in preference to xdebug.mode, so flagging the ini alone would
	 * be silently overridable by the environment PHPStan was started in.
	 *
	 * @param array<string, string> $environmentVariables
	 * @return callable(): void the rollback
	 */
	private static function exportEnvironment(array $environmentVariables): callable
	{
		$original = [];
		foreach ($environmentVariables as $name => $value) {
			$original[$name] = getenv($name);
			putenv($name . '=' . $value);
		}

		return static function () use ($original): void {
			foreach ($original as $name => $value) {
				putenv($value === false ? $name : $name . '=' . $value);
			}
		};
	}

	/**
	 * Exports the composed ini so every descendant PHP process picks it up:
	 * spawned workers read it from PHPRC, forked ones inherit it in memory.
	 *
	 * @return (callable(): void)|null the environment rollback, null when the environment could not be written
	 */
	private static function propagateComposedIni(string $temporaryIni): ?callable
	{
		$phprc = getenv('PHPRC');
		$scanDir = getenv('PHP_INI_SCAN_DIR');

		$restore = static function () use ($phprc, $scanDir): void {
			putenv($phprc === false ? 'PHPRC' : 'PHPRC=' . $phprc);
			putenv($scanDir === false ? 'PHP_INI_SCAN_DIR' : 'PHP_INI_SCAN_DIR=' . $scanDir);
			putenv(self::TEMPORARY_INI_ENV_VARIABLE);
		};

		if (
			!putenv('PHPRC=' . $temporaryIni)
			|| !putenv('PHP_INI_SCAN_DIR=')
			|| !putenv(self::TEMPORARY_INI_ENV_VARIABLE . '=' . $temporaryIni)
		) {
			$restore();
			return null;
		}

		return $restore;
	}

	/**
	 * The restarted process owns the composed ini: it outlives every worker
	 * reading it, and pcntl_exec() left the process that composed it no chance
	 * to delete anything.
	 *
	 * A shutdown function deletes it when the run ends - Ctrl+C and SIGTERM
	 * included, which end the run through the event loop. A watchdog covers
	 * the rest: SIGKILL, and the very crashes some of the restart reasons are
	 * about (https://github.com/phpstan/phpstan/issues/15407).
	 */
	private static function cleanUpComposedIni(): void
	{
		$temporaryIni = (string) getenv(self::TEMPORARY_INI_ENV_VARIABLE);
		if ($temporaryIni === '') {
			return;
		}

		// descendants must not delete the file their siblings are still reading
		putenv(self::TEMPORARY_INI_ENV_VARIABLE);

		// pcntl_fork()ed workers inherit this shutdown function; the first one
		// to end would delete the ini while the rest of the run still reads it
		$ownerPid = getmypid();
		register_shutdown_function(static function () use ($temporaryIni, $ownerPid): void {
			if (getmypid() !== $ownerPid) {
				return;
			}

			@unlink($temporaryIni);
		});

		self::startComposedIniWatchdog($temporaryIni);
	}

	/**
	 * Forks a process that waits for this one to end, whichever way, and then
	 * deletes the composed ini - the role the waiting parent plays for a
	 * proc_open() restart, which pcntl_exec() does not have. It runs before
	 * the autoloader, so it holds next to nothing of the parent's memory.
	 *
	 * It ignores the terminal's signals (a Ctrl+C reaches the whole process
	 * group) and closes the standard streams, so that a caller reading
	 * PHPStan's output through a pipe sees it end with PHPStan. It ends by
	 * SIGKILLing itself: PHP's teardown would run the module shutdown of every
	 * extension it inherited, which a fork-unsafe one wedges (see
	 * ForkedChildTerminator).
	 */
	private static function startComposedIniWatchdog(string $temporaryIni): void
	{
		if (
			!function_exists('pcntl_fork')
			|| !function_exists('pcntl_signal')
			|| !function_exists('posix_getppid')
			|| !function_exists('posix_kill')
		) {
			return;
		}

		$parentPid = getmypid();
		$pid = @pcntl_fork();
		if ($pid !== 0) {
			// the parent, or a failed fork - the shutdown function still covers the common ways out
			return;
		}

		foreach (['SIGINT', 'SIGTERM', 'SIGHUP', 'SIGQUIT'] as $signal) {
			if (!defined($signal)) {
				continue;
			}

			pcntl_signal(constant($signal), SIG_IGN);
		}
		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);

		while (posix_getppid() === $parentPid) {
			usleep(self::WATCHDOG_POLL_INTERVAL_MICROSECONDS);
		}

		@unlink($temporaryIni);
		posix_kill((int) getmypid(), SIGKILL);
	}

	private static function deleteComposedIni(?string $temporaryIni): void
	{
		if ($temporaryIni === null) {
			return;
		}

		@unlink($temporaryIni);
	}

	/**
	 * @return list<string>
	 */
	private static function getPassedThroughArguments(): array
	{
		$arguments = [];

		$loadedIni = php_ini_loaded_file();
		if ($loadedIni !== false) {
			$arguments[] = '-c';
			$arguments[] = $loadedIni;
		}

		$arguments[] = '-d';
		$arguments[] = 'memory_limit=' . ini_get('memory_limit');

		return $arguments;
	}

	/**
	 * The first argument that is not an option — what Symfony resolves the
	 * command name from. The value of a space-separated option can be mistaken
	 * for it, which can only ever produce a name that is not in
	 * NON_RESTARTING_COMMANDS, so the restart happens as it would anyway.
	 *
	 * @param list<string> $argv
	 */
	private static function findCommandName(array $argv): ?string
	{
		foreach (array_slice($argv, 1) as $argument) {
			if ($argument === '' || strpos($argument, '-') === 0) {
				continue;
			}

			return $argument;
		}

		return null;
	}

	/**
	 * Whether the run asked for xdebug to be left alone, through --xdebug or
	 * through the deprecated environment variable.
	 *
	 * @param list<string> $argv
	 */
	public static function isXdebugAllowed(array $argv, string $appName): bool
	{
		if (in_array('--xdebug', $argv, true)) {
			return true;
		}

		$allowed = (string) getenv(strtoupper($appName) . self::ALLOW_XDEBUG_ENV_VARIABLE_SUFFIX);

		return $allowed !== '' && $allowed !== '0';
	}

	/**
	 * @param list<string> $argv
	 */
	private static function isRestartReported(array $argv): bool
	{
		$xdebugHandlerDebug = (string) getenv(self::XDEBUG_HANDLER_DEBUG_ENV_VARIABLE);

		return in_array('-vvv', $argv, true)
			|| in_array('--verbose=3', $argv, true)
			|| ($xdebugHandlerDebug !== '' && $xdebugHandlerDebug !== '0');
	}

	/**
	 * @param non-empty-list<RestartReason> $reasons
	 */
	private static function writeRestartLine(array $reasons): void
	{
		$descriptions = [];
		foreach ($reasons as $reason) {
			$descriptions[] = sprintf('%s (%s)', $reason->getExtension(), $reason->getDescription());
		}

		fwrite(STDERR, sprintf('Restarting process: %s' . PHP_EOL, implode(', ', $descriptions)));
	}

}
