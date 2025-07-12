<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\ShouldNotHappenException;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function fclose;
use function rewind;
use function stream_get_contents;
use function tmpfile;

final class ProcessPromise
{

	/** @var Deferred<string> */
	private Deferred $deferred;

	private ?Process $process = null;

	private bool $canceled = false;

	public function __construct(private LoopInterface $loop, private string $command)
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
		$tmpStdOutResource = tmpfile();
		if ($tmpStdOutResource === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$tmpStdErrResource = tmpfile();
		if ($tmpStdErrResource === false) {
			throw new ShouldNotHappenException('Failed creating temp file for stderr.');
		}

		$this->process = new Process($this->command, fds: [
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
				$this->deferred->reject(new ProcessCrashedException($stdOut . $stdErr));
				return;
			}

			if ($exitCode === 0) {
				$this->deferred->resolve($stdOut);
				return;
			}

			$this->deferred->reject(new ProcessCrashedException($stdOut . $stdErr));
		});

		return $this->deferred->promise();
	}

	private function cancel(): void
	{
		if ($this->process === null) {
			throw new ShouldNotHappenException('Cancelling process before running');
		}
		$this->canceled = true;
		$this->process->terminate();
		$this->deferred->reject(new ProcessCanceledException());
	}

}
