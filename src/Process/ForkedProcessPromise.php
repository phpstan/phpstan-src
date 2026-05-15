<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Command\FixerWorkerRunner;
use PHPStan\Command\Output;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\TcpServer;
use Symfony\Component\Console\Input\InputInterface;
use function fclose;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function posix_kill;
use function rewind;
use function stream_get_contents;
use function tmpfile;
use const SIGTERM;
use const WNOHANG;

/**
 * ProcessPromise backed by pcntl_fork(): the PHPStan Pro worker is forked from
 * the already-booted main process, so it inherits the DI container for free
 * and skips the application re-boot that a {@see SpawnedProcessPromise} pays.
 *
 * The forked child still talks to FixerApplication over the same TCP + NDJSON
 * protocol — only the process-creation mechanism differs.
 */
final class ForkedProcessPromise implements ProcessPromise
{

	private const WAITPID_POLL_INTERVAL = 0.01;

	/** @var Deferred<string> */
	private Deferred $deferred;

	private ?int $childPid = null;

	/** @var resource|null */
	private $stdOut = null;

	private ?TimerInterface $waitTimer = null;

	private bool $canceled = false;

	/**
	 * @param string[] $inceptionFiles
	 * @param mixed[]|null $projectConfigArray
	 */
	public function __construct(
		private LoopInterface $loop,
		private FixerWorkerRunner $fixerWorkerRunner,
		private TcpServer $server,
		private Output $errorOutput,
		private array $inceptionFiles,
		private bool $isOnlyFiles,
		private ?array $projectConfigArray,
		private ?string $configuration,
		private int $serverPort,
		private InputInterface $input,
	)
	{
		$this->deferred = new Deferred(function (): void {
			$this->cancel();
		});
	}

	/**
	 * @return PromiseInterface<string>
	 */
	public function run(): PromiseInterface
	{
		// Created before the fork so the parent can read what the child wrote.
		$tmpStdOut = tmpfile();
		if ($tmpStdOut === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$this->stdOut = $tmpStdOut;

		$pid = pcntl_fork();

		if ($pid === -1) {
			fclose($this->stdOut);
			$this->stdOut = null;
			// Deferred so it runs after FixerApplication has stored the promise.
			$this->loop->futureTick(function (): void {
				$this->deferred->reject(new ProcessCrashedException('pcntl_fork() failed.'));
			});

			return $this->deferred->promise();
		}

		if ($pid === 0) {
			// Child: drop the inherited listening socket immediately, then run
			// the worker on its own fresh event loop and never return.
			$this->server->close();
			$exitCode = $this->fixerWorkerRunner->run(
				$this->errorOutput,
				$this->inceptionFiles,
				$this->isOnlyFiles,
				$this->projectConfigArray,
				$this->configuration,
				$this->serverPort,
				$this->input,
			);
			exit($exitCode);
		}

		// Parent: poll for the child to exit and resolve/reject accordingly.
		$this->childPid = $pid;
		$this->waitTimer = $this->loop->addPeriodicTimer(self::WAITPID_POLL_INTERVAL, function () use ($pid): void {
			$status = 0;
			$result = pcntl_waitpid($pid, $status, WNOHANG);
			if ($result === 0) {
				return;
			}

			$this->cancelWaitTimer();

			$output = '';
			if ($this->stdOut !== null) {
				rewind($this->stdOut);
				$output = (string) stream_get_contents($this->stdOut);
				fclose($this->stdOut);
				$this->stdOut = null;
			}

			if ($this->canceled) {
				// cancel() already rejected the promise; just reap the child.
				return;
			}

			$exitCode = null;
			if ($result > 0 && pcntl_wifexited($status)) {
				$exitStatus = pcntl_wexitstatus($status);
				if ($exitStatus !== false) {
					$exitCode = $exitStatus;
				}
			}

			if ($exitCode === 0) {
				$this->deferred->resolve($output);
				return;
			}

			$this->deferred->reject(new ProcessCrashedException($output));
		});

		return $this->deferred->promise();
	}

	private function cancel(): void
	{
		if ($this->childPid === null) {
			throw new ShouldNotHappenException('Cancelling process before running');
		}
		$this->canceled = true;
		// SIGTERM the child; the waitpid poll timer keeps running so it still
		// gets reaped (otherwise: zombie).
		posix_kill($this->childPid, SIGTERM);
		$this->deferred->reject(new ProcessCanceledException());
	}

	private function cancelWaitTimer(): void
	{
		if ($this->waitTimer === null) {
			return;
		}

		$this->loop->cancelTimer($this->waitTimer);
		$this->waitTimer = null;
	}

}
