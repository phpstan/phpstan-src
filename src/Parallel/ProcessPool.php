<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use React\Socket\TcpServer;
use function array_key_exists;

class ProcessPool
{

	private TcpServer $server;

	/** @var array<string, Process> */
	private array $processes = [];

	public function __construct(TcpServer $server)
	{
		$this->server = $server;
	}

	public function getProcess(string $identifier): Process
	{
		if (!array_key_exists($identifier, $this->processes)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Process %s not found.', $identifier));
		}

		return $this->processes[$identifier];
	}

	public function attachProcess(string $identifier, Process $process): void
	{
		$this->processes[$identifier] = $process;
	}

	public function tryQuitProcess(string $identifier): void
	{
		if (!array_key_exists($identifier, $this->processes)) {
			return;
		}

		$this->quitProcess($identifier);
	}

	private function quitProcess(string $identifier): void
	{
		$process = $this->getProcess($identifier);
		$process->quit();
		unset($this->processes[$identifier]);
		if (count($this->processes) !== 0) {
			return;
		}

		$this->server->close();
	}

	public function quitAll(): void
	{
		foreach (array_keys($this->processes) as $identifier) {
			$this->quitProcess($identifier);
		}
	}

}
