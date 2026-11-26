<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\ShouldNotHappenException;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Throwable;
use function fclose;
use function rewind;
use function stream_get_contents;
use function tmpfile;

/**
 * Parallel worker backed by a freshly spawned PHP process (react/child-process).
 * The spawned worker re-boots the whole application via WorkerCommand.
 *
 * @see ForkedProcess for the pcntl_fork()-based alternative that skips the re-boot.
 */
final class SpawnedProcess extends ProcessBase
{

	private Process $process;

	/** @var resource */
	private $stdOut;

	/** @var resource */
	private $stdErr;

	public function __construct(
		private string $command,
		LoopInterface $loop,
		float $timeoutSeconds,
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
		$tmpStdOut = tmpfile();
		if ($tmpStdOut === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$tmpStdErr = tmpfile();
		if ($tmpStdErr === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stderr.');
		}
		$this->stdOut = $tmpStdOut;
		$this->stdErr = $tmpStdErr;
		$this->process = new Process($this->command, fds: [
			1 => $this->stdOut,
			2 => $this->stdErr,
		]);
		$this->process->start($this->loop);
		$this->setCallbacks($onData, $onError);
		$this->process->on('exit', function ($exitCode, $termSignal) use ($onExit): void {
			$this->cancelTimer();

			$output = '';
			rewind($this->stdOut);
			$output .= stream_get_contents($this->stdOut);

			rewind($this->stdErr);
			$output .= stream_get_contents($this->stdErr);

			$onExit($exitCode, $output, $termSignal);
			fclose($this->stdOut);
			fclose($this->stdErr);
		});
	}

	public function getPid(): ?int
	{
		return $this->process->getPid();
	}

	public function quit(): void
	{
		$this->cancelTimer();
		if (!$this->process->isRunning()) {
			return;
		}

		foreach ($this->process->pipes as $pipe) {
			$pipe->close();
		}

		$this->endConnection();
	}

}
