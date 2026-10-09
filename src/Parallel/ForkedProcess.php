<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\Command\InceptionNotSuccessfulException;
use PHPStan\Process\ForkedChildCrashReporter;
use PHPStan\Process\ForkedChildTerminator;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Socket\TcpServer;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;
use function fclose;
use function function_exists;
use function ini_set;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function pcntl_wifsignaled;
use function pcntl_wtermsig;
use function rewind;
use function stream_get_contents;
use function tmpfile;
use const WNOHANG;

/**
 * Parallel worker backed by pcntl_fork(): the worker is forked from the
 * already-booted main process, so it inherits the DI container for free and
 * skips the application re-boot that a {@see SpawnedProcess} pays.
 *
 * The forked child still talks to ParallelAnalyser over the same TCP + NDJSON
 * protocol — only the process-creation mechanism differs.
 */
final class ForkedProcess extends ProcessBase
{

	private const WAITPID_POLL_INTERVAL = 0.01;

	/** @var resource|null */
	private $stdOut = null;

	/** @var resource|null */
	private $stdErr = null;

	private ?TimerInterface $waitTimer = null;

	private ?int $pid = null;

	/**
	 * @param string[] $analysedFiles
	 */
	public function __construct(
		LoopInterface $loop,
		float $timeoutSeconds,
		private WorkerRunner $workerRunner,
		private TcpServer $server,
		private int $serverPort,
		private string $identifier,
		private array $analysedFiles,
		private ?string $tmpFile,
		private ?string $insteadOfFile,
	)
	{
		parent::__construct($loop, $timeoutSeconds);
	}

	/**
	 * @param callable(mixed[] $json) : void $onData
	 * @param callable(Throwable $exception): void $onError
	 * @param callable(?int $exitCode, string $output, ?int $termSignal) : void $onExit
	 */
	public function start(callable $onData, callable $onError, callable $onExit): void
	{
		$this->setCallbacks($onData, $onError);

		// Created before the fork so the parent can read what the child wrote.
		$tmpStdOut = tmpfile();
		if ($tmpStdOut === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$tmpStdErr = tmpfile();
		if ($tmpStdErr === false) {
			fclose($tmpStdOut);
			throw new ShouldNotHappenException('Failed creating temp file for stderr.');
		}
		$this->stdOut = $tmpStdOut;
		$this->stdErr = $tmpStdErr;

		$pid = pcntl_fork();

		if ($pid === -1) {
			$this->closeCaptureFiles();
			// Deferred so it runs after ParallelAnalyser has attached this
			// process to the pool — otherwise tryQuitProcess() would no-op.
			$this->loop->futureTick(static function () use ($onExit): void {
				$onExit(null, 'pcntl_fork() failed.', null);
			});
			return;
		}

		if ($pid === 0) {
			// Child: drop the inherited listening socket immediately, then run
			// the worker on its own fresh event loop and never return.
			$this->server->close();
			// Leave the shared OPcache alone from here on. What the parent
			// loaded before forking keeps running from shared memory; this
			// worker neither stores what it compiles from now on nor loads
			// what its siblings store. Concurrent population by forked
			// siblings races on the per-process map_ptr slot table: a process
			// linking classes privately (once the shared memory is full, or
			// for any class whose parent is not shared) takes slot offsets
			// from its own counter, a sibling storing a script takes the same
			// offsets for the shared copy, and loading that script only ever
			// bumps the counter upwards - the shared method then runs with a
			// foreign run-time cache and segfaults on its first cache slot
			// (intermittent SIGSEGV in workers, 3 of 10 slevomat runs).
			// Disabling takes effect immediately and is the one OPcache switch
			// that can be flipped at run time. It costs the sharing of classes
			// loaded after the fork (0-2% CPU, worker heaps between the
			// no-OPcache and fully-shared numbers), not the parent's cache.
			ini_set('opcache.enable', '0');
			// memory_get_peak_usage() carries over into the child, so without this a
			// worker would report the main process's peak instead of its own - on an
			// incremental run, the spike taken while loading the result cache, which
			// every worker would then repeat. Restarting the peak here keeps the
			// reported number the worker's own high-water usage, inherited memory it
			// still holds included.
			/** phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName */
			if (function_exists('memory_reset_peak_usage')) {
				\memory_reset_peak_usage();
			}
			/** phpcs:enable */
			ForkedChildCrashReporter::install($tmpStdErr);
			// after the crash reporter: its shutdown function must run first
			ForkedChildTerminator::install();
			$output = new StreamOutput($tmpStdOut);
			try {
				$exitCode = $this->workerRunner->run(
					$output,
					$this->analysedFiles,
					$this->serverPort,
					$this->identifier,
					$this->tmpFile,
					$this->insteadOfFile,
				);
			} catch (InceptionNotSuccessfulException) {
				// a deferred bootstrap file failed - its error is already
				// printed to the child's collected stdout
				exit(1);
			}
			exit($exitCode);
		}

		// Parent: poll for the child to exit and report it through $onExit.
		$this->pid = $pid;
		$this->waitTimer = $this->loop->addPeriodicTimer(self::WAITPID_POLL_INTERVAL, function () use ($pid, $onExit): void {
			$status = 0;
			$result = pcntl_waitpid($pid, $status, WNOHANG);
			if ($result === 0) {
				return;
			}

			$this->cancelWaitTimer();
			$this->cancelTimer();

			$exitCode = null;
			$termSignal = null;
			if ($result > 0 && pcntl_wifexited($status)) {
				$exitStatus = pcntl_wexitstatus($status);
				if ($exitStatus !== false) {
					$exitCode = $exitStatus;
				}
			} elseif ($result > 0 && pcntl_wifsignaled($status)) {
				$signal = pcntl_wtermsig($status);
				if ($signal !== false) {
					$termSignal = $signal;
				}
			}

			$output = '';
			if ($this->stdOut !== null) {
				rewind($this->stdOut);
				$output .= (string) stream_get_contents($this->stdOut);
			}
			if ($this->stdErr !== null) {
				rewind($this->stdErr);
				$output .= (string) stream_get_contents($this->stdErr);
			}
			$this->closeCaptureFiles();

			$onExit($exitCode, $output, $termSignal);
		});
	}

	public function quit(): void
	{
		// Ending the connection makes the child's event loop drain and the
		// child exit; the waitpid poll timer must keep running until then so
		// the child is actually reaped (otherwise: zombie + hang).
		$this->endConnection();
	}

	public function getPid(): ?int
	{
		return $this->pid;
	}

	private function cancelWaitTimer(): void
	{
		if ($this->waitTimer === null) {
			return;
		}

		$this->loop->cancelTimer($this->waitTimer);
		$this->waitTimer = null;
	}

	private function closeCaptureFiles(): void
	{
		if ($this->stdOut !== null) {
			fclose($this->stdOut);
			$this->stdOut = null;
		}
		if ($this->stdErr === null) {
			return;
		}

		fclose($this->stdErr);
		$this->stdErr = null;
	}

}
