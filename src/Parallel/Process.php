<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

class Process
{

	/** @var string */
	private $command;

	/** @var \React\ChildProcess\Process */
	public $process;

	/** @var LoopInterface */
	private $loop;

	/** @var WritableStreamInterface */
	private $in;

	/** @var resource */
	private $stdOut;

	/** @var resource */
	private $stdErr;

	/** @var callable */
	private $onData;

	/** @var callable */
	private $onError;

	/** @var TimerInterface|null */
	private $timer;

	public function __construct(
		string $command,
		LoopInterface $loop
	)
	{
		$this->command = $command;
		$this->loop = $loop;
	}

	public function start(callable $onData, callable $onError, callable $onExit): void
	{
		$tmpStdOut = tmpfile();
		if ($tmpStdOut === false) {
			throw new \PHPStan\ShouldNotHappenException('Failed creating temp file for stdout.');
		}
		$tmpStdErr = tmpfile();
		if ($tmpStdErr === false) {
			throw new \PHPStan\ShouldNotHappenException('Failed creating temp file for stderr.');
		}
		$this->stdOut = $tmpStdOut;
		$this->stdErr = $tmpStdErr;
		$this->process = new \React\ChildProcess\Process($this->command, null, null, [
			1 => $this->stdOut,
			2 => $this->stdErr,
		]);
		$this->process->start($this->loop);
		$this->onData = $onData;
		$this->onError = $onError;
		$this->process->on('exit', function ($exitCode) use ($onExit): void {
			$this->cancelTimer();

			$output = '';
			rewind($this->stdOut);
			$stdOut = stream_get_contents($this->stdOut);
			if (is_string($stdOut)) {
				$output .= $stdOut;
			}

			rewind($this->stdErr);
			$stdErr = stream_get_contents($this->stdErr);
			if (is_string($stdErr)) {
				$output .= $stdErr;
			}
			$onExit($exitCode, $output);
			fclose($this->stdOut);
			fclose($this->stdErr);
		});
	}

	private function cancelTimer(): void
	{
		if ($this->timer === null) {
			return;
		}

		$this->loop->cancelTimer($this->timer);
		$this->timer = null;
	}

	/**
	 * @param mixed[] $data
	 */
	public function request(array $data): void
	{
		$this->cancelTimer();
		$this->in->write($data);
		$this->timer = $this->loop->addTimer(60.0, function (): void {
			$onError = $this->onError;
			$onError(new \Exception('Child process timed out after 60 seconds.'));
		});
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

		// todo should I close/end something here or is it enough to terminate the process?
		$this->in->end();

		// todo what are all the events I should listen to?
		// process: just exit now
		// connection: connection, data, error?
	}

	public function bindConnection(ReadableStreamInterface $out, WritableStreamInterface $in): void
	{
		$out->on('data', function (array $json): void {
			if ($json['action'] !== 'result') {
				return;
			}

			$onData = $this->onData;
			$onData($json['result']);
		});
		$this->in = $in;
		$out->on('error', function (\Throwable $error): void {
			$onError = $this->onError;
			$onError($error);
		});
	}

}
