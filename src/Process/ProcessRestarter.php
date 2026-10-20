<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Composer\XdebugHandler\XdebugHandler;
use Override;
use function array_merge;
use function array_slice;
use function count;
use function extension_loaded;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function getmypid;
use function ini_get;
use function pcntl_exec;
use function pcntl_fork;
use function pcntl_signal;
use function posix_getppid;
use function preg_replace;
use function unlink;
use function usleep;
use const PHP_EOL;
use const SIG_IGN;
use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;
use const STDERR;
use const STDIN;
use const STDOUT;

// see https://github.com/composer/xdebug-handler/issues/174
final class ProcessRestarter extends XdebugHandler
{

	private const WATCHDOG_POLL_MICROSECONDS = 100_000;

	/** @var array<string> */
	private array $extensions = [];

	/** @var list<string>|null */
	private ?array $execIniArgs = null;

	/**
	 * Replaces this process via pcntl_exec() instead of waiting for a child,
	 * with these `-d` entries on top of the stripped ini. TurboProcessRestarter
	 * passes its own entries, so one restart covers both of them.
	 *
	 * @param list<string> $iniArgs `name=value` entries
	 */
	public function setExecIniArgs(array $iniArgs): self
	{
		$this->execIniArgs = $iniArgs;
		return $this;
	}

	/**
	 * Nobody waits for the exec, so a forked process takes the role of the
	 * waiting parent of a proc_open() restart: it ignores Ctrl-C, outlives the
	 * main process and then removes the ini. It ends by exec'ing rm, so PHP's
	 * shutdown sequence (shutdown functions, destructors, Xdebug output) never
	 * runs in it.
	 */
	private function startTmpIniWatchdog(): bool
	{
		$tmpIni = (string) $this->tmpIni;
		$parentPid = getmypid();
		$pid = pcntl_fork();
		if ($pid === -1) {
			return false;
		}
		if ($pid > 0) {
			return true;
		}

		foreach ([SIGINT, SIGTERM, SIGHUP, SIGQUIT] as $signal) {
			pcntl_signal($signal, SIG_IGN);
		}
		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);
		while (posix_getppid() === $parentPid) {
			usleep(self::WATCHDOG_POLL_MICROSECONDS);
		}
		pcntl_exec('/bin/rm', ['-f', $tmpIni]);
		@unlink($tmpIni);
		exit(0);
	}

	#[Override]
	protected function requiresRestart(bool $default): bool
	{
		// pcov has a perf impact similar to xdebug, so we want to disable it
		if (extension_loaded('pcov') && ini_get('pcov.enabled') === '1') {
			$this->extensions[] = 'pcov';
		}

		// tideways hooks deep in the php engine, so it can segfault when running phpstan with turbo.
		if (extension_loaded('tideways')) {
			$this->extensions[] = 'tideways';
		}

		return $default || count($this->extensions) > 0;
	}

	#[Override]
	protected function restart(array $command): void
	{
		if (count($this->extensions) > 0) {
			$content = file_get_contents((string) $this->tmpIni);

			if ($content !== false) {
				foreach ($this->extensions as $extension) {
					$regex = '/^\s*((?:zend_)?extension\s*=.*' . $extension . '.*)$/mi';
					$content = preg_replace($regex, ';$1', $content) . PHP_EOL;
				}
				file_put_contents((string) $this->tmpIni, $content);
			}
		}

		if ($this->execIniArgs !== null && function_exists('pcntl_exec') && $this->startTmpIniWatchdog()) {
			$args = [];
			foreach ($this->execIniArgs as $iniArg) {
				$args[] = '-d';
				$args[] = $iniArg;
			}
			pcntl_exec($command[0], array_merge($args, array_slice($command, 1)));
			// pcntl_exec() returns only on failure, wait for a child instead
		}

		parent::restart($command);
	}

}
