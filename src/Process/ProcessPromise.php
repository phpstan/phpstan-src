<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Process\Runnable\Runnable;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

class ProcessPromise implements Runnable
{

	/** @var LoopInterface */
	private $loop;

	/** @var string */
	private $name;

	/** @var string */
	private $command;

	private Deferred $deferred;

	private ?Process $process = null;

	private bool $canceled = false;

	public function __construct(LoopInterface $loop, string $name, string $command)
	{
		$this->loop = $loop;
		$this->name = $name;
		$this->command = $command;
		$this->deferred = new Deferred();
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return ExtendedPromiseInterface&CancellablePromiseInterface
	 */
	public function run(): CancellablePromiseInterface
	{
		$tmpStdOutResource = tmpfile();
		if ($tmpStdOutResource === false) {
			throw new \PHPStan\ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$tmpStdErrResource = tmpfile();
		if ($tmpStdErrResource === false) {
			throw new \PHPStan\ShouldNotHappenException('Failed creating temp file for stderr.');
		}

		$this->process = new Process($this->command, null, null, [
			1 => $tmpStdOutResource,
			2 => $tmpStdErrResource,
		]);
		$this->process->start($this->loop);

		$this->process->on('exit', function ($exitCode) use ($tmpStdOutResource, $tmpStdErrResource): void {
			if ($this->canceled) {
				fclose($tmpStdOutResource);
				fclose($tmpStdErrResource);
				return;
			}
			rewind($tmpStdOutResource);
			$stdOut = stream_get_contents($tmpStdOutResource);
			fclose($tmpStdOutResource);

			rewind($tmpStdErrResource);
			$stdErr = stream_get_contents($tmpStdErrResource);
			fclose($tmpStdErrResource);

			if ($exitCode === null) {
				$this->deferred->reject(new \PHPStan\Process\ProcessCrashedException($stdOut . $stdErr));
				return;
			}

			if ($exitCode === 0) {
				$this->deferred->resolve($stdOut);
				return;
			}

			$this->deferred->reject(new \PHPStan\Process\ProcessCrashedException($stdOut . $stdErr));
		});

		/** @var ExtendedPromiseInterface&CancellablePromiseInterface */
		return $this->deferred->promise();
	}

	public function cancel(): void
	{
		if ($this->process === null) {
			throw new \PHPStan\ShouldNotHappenException('Cancelling process before running');
		}
		$this->canceled = true;
		$this->process->terminate();
		$this->deferred->reject(new \PHPStan\Process\ProcessCanceledException());
	}

}
