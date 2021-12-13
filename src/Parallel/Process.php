<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Exception;
use Nette\Utils\Json;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use Throwable;
use function fclose;
use function is_string;
use function rewind;
use function sprintf;
use function stream_get_contents;
use function tmpfile;

class Process
{

	private string $command;

	public \React\ChildProcess\Process $process;

	private LoopInterface $loop;

	private float $timeoutSeconds;

	private WritableStreamInterface $in;

	/** @var resource */
	private $stdOut;

	/** @var resource */
	private $stdErr;

	/** @var callable(mixed[] $json) : void */
	private $onData;

	/** @var callable(Throwable $exception): void */
	private $onError;

	private ?TimerInterface $timer = null;

	/** @var mixed[]|null */
	private ?array $lastData = null;

	public function __construct(
		string $command,
		LoopInterface $loop,
		float $timeoutSeconds
	)
	{
		$this->command = $command;
		$this->loop = $loop;
		$this->timeoutSeconds = $timeoutSeconds;
	}

	/**
	 * @param callable(mixed[] $json) : void $onData
	 * @param callable(Throwable $exception): void $onError
	 * @param callable(?int $exitCode, string $output) : void $onExit
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
			if ($this->lastData !== null) {
				$output .= sprintf("\nLast data before exit: %s", Json::encode($this->lastData, Json::PRETTY));
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
		$this->lastData = $data;
		$this->timer = $this->loop->addTimer($this->timeoutSeconds, function (): void {
			$onError = $this->onError;
			$message = sprintf('Child process timed out after %.1f seconds. Try making it longer with parallel.processTimeout setting.', $this->timeoutSeconds);
			if ($this->lastData !== null) {
				$message .= sprintf("\nLast data before exit: %s", Json::encode($this->lastData, Json::PRETTY));
			}
			$onError(new Exception($message));
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

		$this->in->end();
	}

	public function bindConnection(ReadableStreamInterface $out, WritableStreamInterface $in): void
	{
		$out->on('data', function (array $json): void {
			$this->cancelTimer();
			if ($json['action'] !== 'result') {
				return;
			}

			$onData = $this->onData;
			$onData($json['result']);
		});
		$this->in = $in;
		$out->on('error', function (Throwable $error): void {
			$onError = $this->onError;
			$onError($error);
		});
		$in->on('error', function (Throwable $error): void {
			$onError = $this->onError;
			$onError($error);
		});
	}

}
