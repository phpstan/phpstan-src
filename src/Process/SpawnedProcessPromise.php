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

/**
 * A freshly spawned PHP process (react/child-process) as a promise that resolves
 * with its output on success and rejects with ProcessCrashedException otherwise.
 */
final class SpawnedProcessPromise
{

	/** @var Deferred<string> */
	private Deferred $deferred;

	public function __construct(private LoopInterface $loop, private string $command)
	{
		$this->deferred = new Deferred();
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

		$process = new Process($this->command, fds: [
			1 => $tmpStdOutResource,
			2 => $tmpStdErrResource,
		]);
		$process->start($this->loop);

		$process->on('exit', function ($exitCode) use ($tmpStdOutResource, $tmpStdErrResource): void {
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

}
