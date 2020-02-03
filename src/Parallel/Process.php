<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use React\EventLoop\LoopInterface;

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
		$this->stdin->on('error', $onError);
		$this->stdout = new Decoder($this->process->stdout, true, 512, 0, 4 * 1024 * 1024);
		$this->stdout->on('data', $onData);
		$this->stdout->on('error', $onError);

		$this->stdErr = new StreamBuffer($this->process->stderr);
		$this->process->on('exit', function ($exitCode) use ($onExit): void {
			$onExit($exitCode, $this->stdErr->getBuffer());
		});
	}

	/**
	 * @param mixed[] $data
	 */
	public function request(array $data): void
	{
		$this->stdin->write($data);
	}

	public function quit(): void
	{
		if (!$this->process->isRunning()) {
			return;
		}

		foreach ($this->process->pipes as $pipe) {
			$pipe->close();
		}
		$this->process->terminate();
	}

}
