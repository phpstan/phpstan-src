<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class Process
{

	/** @var \React\ChildProcess\Process */
	public $process;

	/** @var LoopInterface */
	private $loop;

	/** @var Encoder */
	private $stdin;

	/** @var Decoder */
	private $stdout;

	/** @var StreamBuffer */
	private $stdErr;

	/** @var callable */
	private $onError;

	/** @var TimerInterface|null */
	private $timer;

	public function __construct(
		\React\ChildProcess\Process $process,
		LoopInterface $loop
	)
	{
		$this->process = $process;
		$this->loop = $loop;
	}

	public function start(callable $onData, callable $onError, callable $onExit): void
	{
		$this->process->start($this->loop);
		$this->stdin = new Encoder($this->process->stdin);
		$this->stdin->on('error', function (\Throwable $error) use ($onError): void {
			$this->cancelTimer();
			$onError($error);
		});
		$this->stdout = new Decoder($this->process->stdout, true, 512, 0, 4 * 1024 * 1024);
		$this->stdout->on('data', function (array $data) use ($onData): void {
			$this->cancelTimer();
			$onData($data);
		});
		$this->stdout->on('error', function (\Throwable $error) use ($onError): void {
			$this->cancelTimer();
			$onError($error);
		});
		$this->onError = $onError;

		$this->stdErr = new StreamBuffer($this->process->stderr);
		$this->process->on('exit', function ($exitCode) use ($onExit): void {
			$this->cancelTimer();
			$onExit($exitCode, $this->stdErr->getBuffer());
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
		$this->stdin->write($data);
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
		$this->process->terminate();
	}

}
